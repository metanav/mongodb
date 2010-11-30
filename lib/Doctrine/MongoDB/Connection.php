<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\MongoDB;

use Doctrine\Common\EventManager;

/**
 * Wrapper for the PHP Mongo class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Connection
{
    /** The PHP Mongo instance. */
    private $mongo;

    /** The server string */
    private $server;

    /** The array of server options to use when connecting */
    private $options = array();

    /** The Configuration instance */
    private $config;

    /**
     * The event manager that is the central point of the event system.
     *
     * @var Doctrine\Common\EventManager
     */
    private $eventManager;

    /**
     * The logger callable.
     *
     * @var mixed
     */
    private $loggerCallable;

    /**
     * Mongo command prefix
     *
     * @var string
     */
    private $cmd;

    /**
     * Array holding selected databases.
     *
     * @var array
     */
    private $databases = array();

    /**
     * Create a new Mongo wrapper instance.
     *
     * @param mixed $server A string server name, an existing Mongo instance or can be omitted.
     * @param array $options 
     */
    public function __construct($server = null, array $options = array(), Configuration $config = null, EventManager $evm = null)
    {
        if ($server instanceof \Mongo) {
            $this->mongo = $server;
        } elseif ($server !== null) {
            $this->server = $server;
            $this->options = $options;
        }
        $this->config = $config ? $config : new Configuration();
        $this->eventManager = $evm ? $evm : new EventManager();
        $this->loggerCallable = $this->config->getLoggerCallable();
        $this->cmd = $this->config->getMongoCmd();
    }

    public function initialize()
    {
        if ($this->mongo === null) {
            if ($this->server) {
                $this->mongo = new \Mongo($this->server, $this->options);
            } else {
                $this->mongo = new \Mongo();
            }
        }
    }

    /**
     * Log something using the configured logger callable.
     *
     * @param array $log The array of data to log.
     */
    public function log(array $log)
    {
        call_user_func_array($this->loggerCallable, array($log));
    }

    /**
     * Set the PHP Mongo instance to wrap.
     *
     * @param Mongo $mongo The PHP Mongo instance
     */
    public function setMongo(\Mongo $mongo)
    {
        $this->initialize();
        $this->mongo = $mongo;
    }

    /**
     * Returns the PHP Mongo instance being wrapped.
     *
     * @return Mongo
     */
    public function getMongo()
    {
        return $this->mongo;
    }

    /** @proxy */
    public function close()
    {
        $this->initialize();
        return $this->mongo->close();
    }

    /** @proxy */
    public function connect()
    {
        $this->initialize();
        return $this->mongo->connect();
    }

    /** @proxy */
    public function connectUntil()
    {
        $this->initialize();
        return $this->mongo->connectUntil();
    }

    /** @proxy */
    public function dropDB($db)
    {
        $this->initialize();
        return $this->mongo->dropDB($db);
    }

    /** @proxy */
    public function __get($key)
    {
        $this->initialize();
        return $this->mongo->$key;
    }

    /** @proxy */
    public function listDBs()
    {
        $this->initialize();
        return $this->mongo->listDBs();
    }

    /** @proxy */
    public function selectCollection($db, $collection)
    {
        $this->initialize();
        return $this->selectDB($db)->selectCollection($collection);
    }

    /** @proxy */
    public function selectDB($name)
    {
        if ( ! isset($this->databases[$name])) {
            $this->initialize();
            $db = $this->mongo->selectDB($name);
            $this->databases[$name] = new Database(
                $db, $this->eventManager, $this->loggerCallable, $this->cmd
            );
        }
        return $this->databases[$name];
    }

    /** @proxy */
    public function __toString()
    {
        $this->initialize();
        return $this->mongo->__toString();
    }
}