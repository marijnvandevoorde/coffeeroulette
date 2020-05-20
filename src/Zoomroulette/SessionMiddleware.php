<?php


namespace Teamleader\Zoomroulette\Zoomroulette;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SlimSession\Helper;

/**
 * Session middleware
 *
 * Very much stolen from Slim\Middleware by Bryan Horna, but with a PP that this the session becomes an attribute of the request.
 *
 * Keep in mind this relies on PHP native sessions, so for this to work you must
 * have that enabled and correctly working.
 *
 * @author Marijn Vandevoorde
 */
class SessionMiddleware
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $defaults = [
            'lifetime' => '20 minutes',
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'httponly' => false,
            'name' => 'slim_session',
            'autorefresh' => false,
            'handler' => null,
            'ini_settings' => [],
        ];
        $settings = array_merge($defaults, $settings);

        if (is_string($lifetime = $settings['lifetime'])) {
            $settings['lifetime'] = strtotime($lifetime) - time();
        }
        $this->settings = $settings;

        $this->iniSet($settings['ini_settings']);
        // Just override this, to ensure package is working
        if (ini_get('session.gc_maxlifetime') < $settings['lifetime']) {
            $this->iniSet([
                'session.gc_maxlifetime' => $settings['lifetime'] * 2,
            ]);
        }
    }

    /**
     * Called when middleware needs to be executed.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler PSR7 handler
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(
        Request $request,
        RequestHandler $handler
    ): Response {
        $this->startSession();
        return $handler->handle($request->withAttribute('session', new Helper()));
    }

    /**
     * Start session
     */
    protected function startSession()
    {
        $inactive = session_status() === PHP_SESSION_NONE;
        if (!$inactive) {
            return;
        }

        $settings = $this->settings;
        $name = $settings['name'];

        session_set_cookie_params(
            $settings['lifetime'],
            $settings['path'],
            $settings['domain'],
            $settings['secure'],
            $settings['httponly']
        );

        // Refresh session cookie when "inactive",
        // else PHP won't know we want this to refresh
        if ($settings['autorefresh'] && isset($_COOKIE[$name])) {
            setcookie(
                $name,
                $_COOKIE[$name],
                time() + $settings['lifetime'],
                $settings['path'],
                $settings['domain'],
                $settings['secure'],
                $settings['httponly']
            );
        }

        session_name($name);

        $handler = $settings['handler'];
        if ($handler) {
            if (!($handler instanceof \SessionHandlerInterface)) {
                $handler = new $handler();
            }
            session_set_save_handler($handler, true);
        }

        session_cache_limiter(false);
        session_start();
    }

    protected function iniSet($settings)
    {
        foreach ($settings as $key => $val) {
            if (strpos($key, 'session.') === 0) {
                ini_set($key, $val);
            }
        }
    }
}
