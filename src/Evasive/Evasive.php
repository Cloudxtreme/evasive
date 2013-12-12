<?php
namespace Evasive;

use Monolog\Logger;
use Monolog\Handler\SyslogHandler;
use Monolog\Formatter\LineFormatter;

class Evasive
{

    /**
     * @const string
     */
    const VERSION = '1.0.0';

    /**
     * How many identical requests to a specific URI a user can make over the pageInternal interval
     *
     * @var int
     */
    private $pageCount = 5;

    /**
     * blocking interval
     *
     * @var int
     */
    private $pageInternal = 10;

    /**
     * If a user exceeds these limits, they are blacklisted for a set amount of time
     * During this time, any requests they make will return a 403 Forbidden error.
     *
     * @var int
     */
    private $blockingPeriod = 60;

    /**
     * the HTTP methods to be monitored
     *
     * @var array
     */
    private $pageRequestMethods = [
        'GET', 'POST', 'DELETE'
    ];

    /**
     * storage
     *
     * @var StorageInterface
     */
    private $storage = null;

    /**
     * logger
     *
     * @var Logger
     */
    private $logger = null;

    /**
     * constructor
     *
     * @param string $type            
     * @param array $options            
     * @throws \Exception
     */
    public function __construct($type, array $options = [])
    {
        if (! is_string($type)) {
            throw new RunTimeException(sprintf('%s expects the $type argument to be a string class name; received "%s"', __METHOD__, (is_object($type) ? get_class($type) : gettype($type))));
        }
        
        if (! class_exists($type)) {
            $class = __NAMESPACE__ . '\\Storage\\' . $type;
            if (! class_exists($class)) {
                throw new RunTimeException(sprintf('%s expects the $type argument to be a valid class name; received "%s"', __METHOD__, $type));
            }
            $type = $class;
        }
        
        $this->storage = new $type($options);
        
        if (array_key_exists('pageCount', $options)) {
            $this->pageCount = $options['pageCount'];
        }
        
        if (array_key_exists('pageInternal', $options)) {
            $this->pageInternal = $options['pageInternal'];
        }
        
        if (array_key_exists('blockingPeriod', $options)) {
            $this->blockingPeriod = $options['blockingPeriod'];
        }
        
        if (array_key_exists('pageRequestMethods', $options)) {
            $this->pageRequestMethods = $options['pageRequestMethods'];
        }
        
        $this->logger = new Logger('application');
        $syslog = new SyslogHandler('Evasive');
        $formatter = new LineFormatter("%channel%.%level_name%: %message% %extra%");
        $syslog->setFormatter($formatter);
        $this->logger->pushHandler($syslog);
    }

    /**
     * defend by monitor requests
     */
    public function defend()
    {
        $lastRequest = $this->getRequest();
        
        if (empty($lastRequest)) {
            $this->logRequest();
        } else 
            if ($lastRequest['blocked'] && $lastRequest['blocked'] > time() - $this->blockingPeriod) {
                $this->blockRequest();
            } else 
                if ($lastRequest['ip_address'] == $this->getIpAddress() && $lastRequest['request_uri'] == $this->getRequestUri() && $lastRequest['timestamp'] > time() - $this->pageInternal) {
                    
                    if ($lastRequest['request_count'] >= $this->pageCount) {
                        
                        $this->storage->update([
                            'blocked' => time()
                        ]);
                        
                        $this->blockRequest();
                    } else {
                        $this->updateLastRequestCounter($lastRequest['request_count'] + 1);
                    }
                } else {
                    $this->logRequest();
                }
    }

    /**
     * returns the info on the last user's request
     *
     * @return NULL array
     */
    public function getRequest()
    {
        return $this->storage->get();
    }

    /**
     * block and log the user request
     */
    private function blockRequest()
    {
        if ($this->logger instanceof Logger) {
            $this->logger->log(Logger::INFO, sprintf("blocked a requests flood for page %s%s from ip: %s with cookie %s", $this->getHttpHost(), $this->getRequestUri(), $this->getIpAddress(), $this->getCookie()));
            header('HTTP/1.0 403 Forbidden');
        }
        
        exit(sprintf('please wait %s seconds and refresh the page', $this->blockingPeriod));
    }

    /**
     * log the request into the storage adapter
     */
    public function logRequest()
    {
        $requestMethod = $this->getRequestMethod();
        
        if (! in_array($requestMethod, $this->pageRequestMethods)) {
            return;
        }
        
        $data = [
            'request_uri' => $this->getRequestUri(),
            'ip_address' => $this->getIpAddress(),
            'timestamp' => time(),
            'request_method' => $requestMethod,
            'blocked' => false,
            'request_count' => 1
        ];
        
        $this->storage->store($data);
    }

    /**
     *
     * @param int $counter            
     */
    public function updateLastRequestCounter($count)
    {
        $this->storage->update([
            'request_count' => $count
        ]);
    }

    
    /**
     * How many identical requests to a specific URI a user can make
     * 
     * @param int $pageCount
     */
    public function setPageCount($pageCount) {
        $this->pageCount = $pageCount;
    }
    
    /**
     * How many identical requests to a specific URI a user can make
     * 
     * @return int
     */
    public function getPageCount() {
        return $this->pageCount;
    }
    
    /**
     * How many identical requests to a specific URI a user can make
     *
     * @param int $pageCount
     */
    public function setPageInterval($pageInterval) {
        $this->pageInternal = $pageInterval;
    }
    
    /**
     * How many identical requests to a specific URI a user can make
     *
     * @return int
     */
    public function getPageInterval() {
        return $this->pageInternal;
    }
    
    
    private function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    private function getIpAddress()
    {
        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }

    private function getRequestUri()
    {
        return strtok($_SERVER["REQUEST_URI"], '?');
    }

    private function getHttpHost()
    {
        return $_SERVER['HTTP_HOST'];
    }

    private function getCookie()
    {
        if (isset($_SERVER['HTTP_COOKIE'])) {
            return;
            urldecode($_SERVER['HTTP_COOKIE']);
        }
        return '';
    }
}
