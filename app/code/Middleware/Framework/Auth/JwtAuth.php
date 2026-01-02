<?php
namespace Middleware\Framework\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Yii;
use yii\filters\auth\AuthMethod;
use yii\web\UnauthorizedHttpException;
use yii\web\IdentityInterface;

/**
 * JWT Authentication Method
 *
 * Usage in controller:
 * ```php
 * public function behaviors(): array
 * {
 *     $behaviors = parent::behaviors();
 *     $behaviors['authenticator'] = [
 *         'class' => JwtAuth::class,
 *         'optional' => ['options'],
 *     ];
 *     return $behaviors;
 * }
 * ```
 */
class JwtAuth extends AuthMethod
{
    /**
     * @var string HTTP header containing the token
     */
    public string $header = 'Authorization';

    /**
     * @var string Pattern to extract token from header
     */
    public string $pattern = '/^Bearer\s+(.*?)$/';

    /**
     * @var string Algorithm for JWT encoding/decoding
     */
    public string $algorithm = 'HS256';

    /**
     * @var int Token expiration time in seconds (default: 1 hour)
     */
    public int $expiration = 3600;

    /**
     * @var string|null Custom JWT key, if null will use app params
     */
    public ?string $jwtKey = null;

    /**
     * @var bool Whether to validate token expiration
     */
    public bool $validateExpiration = true;

    /**
     * @var bool Whether to validate token signature
     */
    public bool $validateSignature = true;

    /**
     * @var array Additional claims to validate
     */
    public array $requiredClaims = [];

    /**
     * Authenticates the current user
     *
     * @param \yii\web\User $user
     * @param \yii\web\Request $request
     * @param \yii\web\Response $response
     * @return IdentityInterface|null the authenticated user identity
     * @throws UnauthorizedHttpException
     */
    public function authenticate($user, $request, $response): ?IdentityInterface
    {
        $authHeader = $request->headers->get($this->header);

        if ($authHeader === null) {
            return null;
        }

        if (!preg_match($this->pattern, $authHeader, $matches)) {
            $this->handleFailure($response, 'Invalid authorization header format');
        }

        $token = $matches[1];

        try {
            $decoded = $this->decodeToken($token);

            // Validate required claims
            $this->validateClaims($decoded);

            // Get user identity
            $identity = $this->getUserIdentity($decoded, $user);

            if ($identity === null) {
                $this->handleFailure($response, 'User not found');
            }

            return $identity;

        } catch (ExpiredException $e) {
            $this->handleFailure($response, 'Token has expired');
        } catch (SignatureInvalidException $e) {
            $this->handleFailure($response, 'Invalid token signature');
        } catch (\Exception $e) {
            $this->handleFailure($response, 'Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Decode JWT token
     *
     * @param string $token
     * @return object
     * @throws \Exception
     */
    protected function decodeToken(string $token): object
    {
        $key = $this->getJwtKey();

        if (empty($key)) {
            throw new \RuntimeException('JWT key is not configured');
        }

        JWT::$leeway = 60; // Allow 60 seconds leeway for clock skew

        return JWT::decode($token, new Key($key, $this->algorithm));
    }

    /**
     * Generate JWT token
     *
     * @param IdentityInterface $identity
     * @param array $additionalClaims
     * @return string
     */
    public function generateToken(IdentityInterface $identity, array $additionalClaims = []): string
    {
        $now = time();

        $payload = array_merge([
            'iss' => Yii::$app->request->hostInfo, // Issuer
            'aud' => Yii::$app->request->hostInfo, // Audience
            'iat' => $now, // Issued at
            'nbf' => $now, // Not before
            'exp' => $now + $this->expiration, // Expiration
            'sub' => $identity->getId(), // Subject (user ID)
            'jti' => $this->generateJti(), // JWT ID
        ], $additionalClaims);

        $key = $this->getJwtKey();

        return JWT::encode($payload, $key, $this->algorithm);
    }

    /**
     * Validate token claims
     *
     * @param object $decoded
     * @throws UnauthorizedHttpException
     */
    protected function validateClaims(object $decoded): void
    {
        foreach ($this->requiredClaims as $claim) {
            if (!isset($decoded->$claim)) {
                throw new UnauthorizedHttpException("Missing required claim: {$claim}");
            }
        }

        // Validate issuer if configured
        if (isset(Yii::$app->params['jwtIssuer'])) {
            $expectedIssuer = Yii::$app->params['jwtIssuer'];
            if (!isset($decoded->iss) || $decoded->iss !== $expectedIssuer) {
                throw new UnauthorizedHttpException('Invalid token issuer');
            }
        }

        // Validate audience if configured
        if (isset(Yii::$app->params['jwtAudience'])) {
            $expectedAudience = Yii::$app->params['jwtAudience'];
            if (!isset($decoded->aud) || $decoded->aud !== $expectedAudience) {
                throw new UnauthorizedHttpException('Invalid token audience');
            }
        }
    }

    /**
     * Get user identity from decoded token
     *
     * @param object $decoded
     * @param \yii\web\User $user
     * @return IdentityInterface|null
     */
    protected function getUserIdentity(object $decoded, $user): ?IdentityInterface
    {
        if (!isset($decoded->sub)) {
            return null;
        }

        return $user->loginByAccessToken($decoded->sub, get_class($this));
    }

    /**
     * Get JWT secret key
     *
     * @return string
     */
    protected function getJwtKey(): string
    {
        return $this->jwtKey ?? Yii::$app->params['jwtKey'] ?? '';
    }

    /**
     * Generate unique JWT ID
     *
     * @return string
     */
    protected function generateJti(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Refresh token with new expiration
     *
     * @param string $token
     * @return string
     * @throws \Exception
     */
    public function refreshToken(string $token): string
    {
        $decoded = $this->decodeToken($token);

        // Create new token with extended expiration
        $now = time();
        $payload = (array) $decoded;
        $payload['iat'] = $now;
        $payload['exp'] = $now + $this->expiration;
        $payload['jti'] = $this->generateJti();

        $key = $this->getJwtKey();

        return JWT::encode($payload, $key, $this->algorithm);
    }

    /**
     * Verify token without authenticating
     *
     * @param string $token
     * @return bool
     */
    public function verifyToken(string $token): bool
    {
        try {
            $this->decodeToken($token);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract claims from token without full validation
     *
     * @param string $token
     * @return array|null
     */
    public function getClaims(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(JWT::urlsafeB64Decode($parts[1]), true);
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function handleFailure($response): void
    {
        $response->headers->set('WWW-Authenticate', 'Bearer realm="API"');
        throw new UnauthorizedHttpException('Unauthorized');
    }
}