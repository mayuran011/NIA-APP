<?php
/**
 * MySQL database wrapper (singleton).
 * Optional query cache: fetchCached($key, $ttl, $callback) for heavy reads (disk cache in tmp/querycache).
 */

if (!defined('in_nia_app')) {
    exit;
}

class NiaDB {
    /** @var \PDO */
    private $pdo;
    /** @var self */
    private static $instance;

    private function __construct() {
        // #region agent log
        $logPath = defined('ABSPATH') ? (ABSPATH . 'debug-785c75.log') : (__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'debug-785c75.log');
        @file_put_contents($logPath, json_encode(['sessionId'=>'785c75','id'=>'log_'.uniqid(),'timestamp'=>round(microtime(true)*1000),'location'=>'class.db.php:__construct','message'=>'PDO connection about to be created','data'=>['pid'=>getmypid(),'instanceCount'=>'(singleton)'],'hypothesisId'=>'A,C'])."\n", LOCK_EX | FILE_APPEND);
        // #endregion
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function instance() {
        // #region agent log
        $logPath = defined('ABSPATH') ? (ABSPATH . 'debug-785c75.log') : (__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'debug-785c75.log');
        $creating = self::$instance === null;
        @file_put_contents($logPath, json_encode(['sessionId'=>'785c75','id'=>'log_'.uniqid(),'timestamp'=>round(microtime(true)*1000),'location'=>'class.db.php:instance','message'=>'NiaDB::instance() called','data'=>['creatingNew'=>$creating,'pid'=>getmypid()],'hypothesisId'=>'B,C'])."\n", LOCK_EX | FILE_APPEND);
        // #endregion
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** @return \PDO */
    public function pdo() {
        return $this->pdo;
    }

    public function prefix() {
        return DB_PREFIX;
    }

    public function query($sql, array $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, array $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, array $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function replacePrefix($sql) {
        return str_replace('#_', $this->prefix(), $sql);
    }

    /**
     * Optional query cache for heavy reads. Runs $callback() and caches result by $key for $ttl seconds (disk in tmp/querycache).
     * @param string $key Cache key (e.g. 'videos_browse_0_24')
     * @param int $ttl Seconds; 0 = use option cache_ttl
     * @param callable $callback function(): mixed
     * @return mixed
     */
    public function fetchCached($key, $ttl, callable $callback) {
        $cache_dir = (defined('TMP_FOLDER') ? TMP_FOLDER : (ABSPATH . 'tmp')) . DIRECTORY_SEPARATOR . 'querycache';
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0755, true);
        }
        $file = $cache_dir . DIRECTORY_SEPARATOR . md5($key) . '.cache';
        if ($ttl <= 0 && function_exists('get_option')) {
            $ttl = (int) get_option('cache_ttl', 3600);
        }
        if ($ttl > 0 && is_file($file) && filemtime($file) + $ttl >= time()) {
            $raw = @file_get_contents($file);
            if ($raw !== false && $raw !== '') {
                $data = @unserialize($raw);
                if ($data !== false) {
                    return $data;
                }
            }
        }
        $data = $callback();
        if ($ttl > 0) {
            @file_put_contents($file, serialize($data), LOCK_EX);
        }
        return $data;
    }
}
