<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 31/07/2019
 * Time: 22:52
 */

namespace Plexus;

use Plexus\DataType\Collection;
use Plexus\Utils\Path;

class Configuration {

    /**
     * @var bool|string
     */
    protected $config_dirpath;

    /**
     * @var bool|string
     */
    protected $config_filepath;

    /**
     * @var
     */
    protected $config_name;

    /**
     * @var
     */
    protected $config_content;

    /**
     * Configuration constructor.
     * @param $config_name
     * @param $config_dirpath
     * @throws \Exception
     */
    public function __construct($config_name, $config_dirpath) {

        $this->config_name = $config_name;

        $this->config_dirpath = Path::normalize($config_dirpath);

        if (!is_dir($this->config_dirpath)) {
            throw new \Exception(sprintf("Le répertoire de configuration '%s' n'existe pas", $this->config_dirpath));
        }

        $this->config_filepath = Path::build($this->config_dirpath, $config_name.'.yaml');
        if (!file_exists($this->config_filepath)) {
            if (!touch($this->config_filepath)) {
                throw new \Exception(sprintf("Le répertoire de créer le fichier de configuration '%s'", $this->config_filepath));
            }
        }
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    public function read() {
        if (!$this->config_content) {
            try {
                $this->config_content = \Symfony\Component\Yaml\Yaml::parseFile($this->config_filepath);
            } catch (\Exception $e) {
                throw new \Exception(sprintf("Impossible de lire correctement le contenu du fichier de configuration '%s' (%s)", $this->config_filepath, $e->getMessage()), 0, $e);
            }
        }
        return new Collection($this->config_content);
    }

    /**
     * @param Configuration $configuration
     * @param bool $override
     * @return $this
     * @throws \Exception
     */
    public function appendConfiguration(Configuration $configuration, $override=false) {
        try {
            $configuration->read()->each(function($name, $value) use ($override) {
                if (!isset($this->config_content[$name]) || $override) {
                    $this->config_content[$name] = $value;
                }
            }, false);
        } catch (\Exception $e) {
            throw new \Exception("Une erreur est survenue lors de la fusion des configurations", 0, $e);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->config_name;
    }
}