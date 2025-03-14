<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 24/07/2019
 * Time: 19:45
 */

namespace Plexus;


use Plexus\DataType\Collection;

class Response {

    /**
     * @var Collection
     */
    protected $headers;

    protected $body;

    protected $status_code;

    protected $locked;

    protected $sent;

    public function __construct() {
        $this->headers = new Collection();
        $this->body = "";
        $this->status_code = 0;
        $this->locked = false;
        $this->sent = false;
    }

    /**
     * @param $code
     */
    public function setStatusCode($code) {
        $this->status_code = (int) $code;
    }

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->status_code;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     * @throws \Exception
     */
    public function header($name, $value) {
        if ($this->locked) {
            throw new \Exception('The response is locked and can be modified');
        }
        $this->headers->set($name, $value);
       return $this;
    }

    /**
     * @param null $content
     * @return $this|Response
     * @throws \Exception
     */
    public function body($content=null) {
        if ($content !== null) {
            if ($this->locked) {
                throw new \Exception('The response is locked and can be modified');
            }
            $this->body = $content;
            return $this;
        }
        return $this->body();
    }

    /**
     * @param $content
     * @return $this
     * @throws \Exception
     */
    public function prepend($content) {
        if ($this->locked) {
            throw new \Exception('The response is locked and can be modified');
        }
        $this->body = $content.$this->body;
        return $this;
    }

    /**
     * @param $content
     * @return $this
     * @throws \Exception
     */
    public function append($content) {
        if ($this->locked) {
            throw new \Exception('The response is locked and can be modified');
        }
        $this->body = $this->body.$content;
        return $this;
    }

    /**
     * @return $this
     */
    public function lock() {
        $this->locked = true;
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function unlock() {
        if ($this->sent) {
            throw new \Exception('The response has already been sent and cannot be unlocked');
        }
        $this->locked = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked() {
        return $this->locked;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function noCache()  {
        $this->header('Pragma', 'no-cache');
        $this->header('Cache-Control', 'no-store, no-cache');

        return $this;
    }

    /**
     * @return string
     */
    public function httpStatusLine() {
        return sprintf('HTTP/1.1 %s', $this->status_code);
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function send() {

        if ($this->sent) {
            throw new \Exception('The response has already been sent');
        }

        $this->sendHeaders();
        $this->sendBody();

        $this->sent = true;
        $this->locked = true;

        return $this;
    }

    /**
     * @param bool $override
     * @return $this
     */
    public function sendHeaders($override=false) {

        if (headers_sent() && !$override) {
            return $this;
        }

        header($this->httpStatusLine());

        foreach ($this->headers->getArray() as $name => $value) {
            header($name.': '.$value, false);
        }
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function sendBody() {

        if ($this->sent) {
            throw new \Exception('The response has already been sent');
        }

        echo (string) $this->body;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSent() {
        return $this->sent;
    }

    /**
     * @param $url
     * @param int $code
     * @return $this
     * @throws \Exception
     */
    public function redirect($url, $code=303) {
        $this->status_code = $code;
        $this->header('Location', $url);
        $this->send();
        return $this;
    }

    /**
     * @param $filepath
     * @param null $filename
     * @param null $mimetype
     * @param bool $download
     * @return $this
     * @throws \Exception
     */
    public function file($filepath, $filename=null, $mimetype=null, $download=false) {

        if ($this->sent) {
            throw new \Exception('The response has already been sent');
        }

        $this->body('');
        $this->noCache();

        if (null === $filename) {
            $filename = basename($filepath);
        }
        if (null === $mimetype) {
            $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filepath);
        }

        $this->setStatusCode(200);
        $this->header('Content-type', $mimetype);
        $this->header('Content-Disposition', (($download) ? 'attachement' : 'inline') . '; filename="'.$filename.'"');
        $this->header('Content-length', filesize($filepath));

        // Send our response data
        $this->sendHeaders();

        readfile($filepath);

        $this->sendBody();

        // Lock the response from further modification
        $this->lock();

        // Mark as sent
        $this->sent = true;

        return $this;
    }

    /**
     * @param $filepath
     * @param null $filename
     * @param null $mimetype
     * @return $this
     * @throws \Exception
     */
    public function file_download($filepath, $filename=null, $mimetype=null) {
        $this->file($filepath, $filename, $mimetype, true);
        return $this;
    }

    /**
     * Use X-Sendfile to perform the request
     *
     * @param $filepath
     * @param null $filename
     * @param null $mimetype
     * @param bool $download
     * @return $this
     * @throws \Exception
     */
    public function xsendfile($filepath, $filename=null, $mimetype=null, $download=false, $cache=false) {

        if ($this->sent) {
            throw new \Exception('The response has already been sent');
        }

        $this->body('');
        if (!$cache) {
            $this->noCache();
        }

        if (null === $filename) {
            $filename = basename($filepath);
        }
        if (null === $mimetype) {
            $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filepath);
        }

        $this->setStatusCode(200);
        $this->header('X-Sendfile', $filepath);
        $this->header('X-Accel-Redirect', $filepath);
        $this->header('Content-type', $mimetype);
        $this->header('Content-Disposition', (($download) ? 'attachement' : 'inline') . '; filename="'.$filename.'"');

        // Send our response data
        $this->sendHeaders();

        // Lock the response from further modification
        $this->lock();

        // Mark as sent
        $this->sent = true;

        return $this;
    }

    /**
     * Use X-Sendfile to perform the request
     *
     * @param $filepath
     * @param null $filename
     * @param null $mimetype
     * @return $this
     * @throws \Exception
     */
    public function xsendfile_download($filepath, $filename=null, $mimetype=null) {
        $this->xsendfile($filepath, $filename, $mimetype, true);
        return $this;
    }

    /**
     * @param $object
     * @return $this
     * @throws \Exception
     */
    public function json($object) {
        $this->body('');
        $this->noCache();

        $json = json_encode($object);


        $this->header('Content-Type', 'application/json');
        $this->body($json);

        $this->send();

        return $this;
    }



}
