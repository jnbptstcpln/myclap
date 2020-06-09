<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/08/2019
 * Time: 00:40
 */

namespace Plexus\Service;


use Plexus\Application;
use Plexus\Exception\BundleException;
use Plexus\Exception\ConfigurationException;
use Plexus\Exception\FileException;

use Plexus\Utils\Path;
use Plexus\Utils\RegExp;

class AssetsManager extends AbstractService {

    /**
     * @var string
     */
    protected $src_folder;

    /**
     * @var string
     */
    protected $dist_folder;

    /**
     * @var string
     */
    protected $base_uri;

    /**
     * AssetsManager constructor.
     * @param Application $application
     * @throws ConfigurationException
     * @throws \Exception
     */
    public function __construct(Application $application) {
        parent::__construct("AssetsManager", $application);

        if ($this->getContainer()->isService('Renderer')) {
            $this->getContainer()->getService('Renderer')->addFunction('asset_url', [$this, 'asset_url']);
        }

        $configuration = $this->getConfiguration();

        if (!$configuration->isset('source')) {
            throw new ConfigurationException(sprintf("Vous devez spécifier un dossier source 'source' (relatif au dossier racine de l'application) dans la configuration du service AssetsManager"));
        }

        $this->src_folder = Path::build($this->application->getRootPath(), $configuration->get('source'));
        if (!file_exists($this->src_folder)) {
            if (!mkdir($this->src_folder)) {
                throw new \Exception(sprintf("Impossible de créer le dossier '%s' lors de l'initilisation de FileManager", $this->src_folder));
            }
        }

        $this->base_uri = "/assets";
        $this->dist_folder = Path::build($this->application->getRootPath(), 'public', "assets");
        if (!file_exists($this->dist_folder)) {
            if (!mkdir($this->dist_folder)) {
                throw new \Exception(sprintf("Impossible de créer le dossier '%s' lors de l'initilisation de FileManager", $this->dist_folder));
            }
        }
    }

    /**
     * @param $asset_identifier
     * @return bool|string
     * @throws BundleException
     * @throws FileException
     */
    public function asset_url($asset_identifier) {
        $path = $this->identifierToPath($asset_identifier);
        $this->buildDistPath($path);

        $src_path = Path::build($this->src_folder, $path);
        $dist_path = Path::build($this->dist_folder, $path);

        if (!file_exists($src_path)) {
            throw new FileException(sprintf("Le fichier ressource '%s' n'existe pas"));
        }

        $src_sha1 = sha1_file($src_path);
        $dist_sha1 = file_exists($dist_path) ? sha1_file($dist_path) : '';

        if ($src_sha1 != $dist_sha1) {
            if (!copy($src_path, $dist_path)) {
                throw new FileException(sprintf("Impossible de copier le fichier ressource '%s' à l'emplacement '%s'", $src_path, $dist_path));
            }
        }

        return sprintf("%s/%s?hash=%s", $this->base_uri, $path, $src_sha1);
    }

    /**
     * @param $identifier
     * @return bool|string
     * @throws BundleException
     */
    private function identifierToPath($identifier) {

        $identifier_parts = explode(':', $identifier);
        $filename = array_pop($identifier_parts);

        $path = "";
        foreach ($identifier_parts as $item) {
            $pattern = "/[\w]/";
            if (!RegExp::matches($pattern, $item)) {
                throw new BundleException(sprintf("'%s' n'est pas un nom de bundle d'asset valide", $item));
            }
            $path = Path::build($path, $item);
        }
        return Path::build($path, $filename);
    }

    /**
     * @param $path
     * @throws BundleException
     */
    private function buildDistPath($path) {
        $path =  join(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $path), 0, -1));
        if (!file_exists(Path::build($this->dist_folder, $path))) {
            $temp_path = $this->dist_folder;
            foreach (explode(DIRECTORY_SEPARATOR, $path) as $name) {
                $temp_path = Path::build($temp_path, $name);
                if (!file_exists($temp_path)) {
                    if (!mkdir($temp_path)) {
                        throw new BundleException(sprintf("Impossible de créer le dossier '%s' lors du chargements des ressources", $temp_path));
                    }
                }
            }
        }

    }
}