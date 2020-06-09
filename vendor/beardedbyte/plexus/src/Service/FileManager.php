<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 02/08/2019
 * Time: 23:30
 */

namespace Plexus\Service;


use Plexus\Application;
use Plexus\Exception\BundleException;
use Plexus\Exception\ConfigurationException;
use Plexus\Exception\FileException;
use Plexus\Utils\Path;
use Plexus\Utils\Randomizer;
use Plexus\Utils\RegExp;

class FileManager extends AbstractService {

    protected $folder;

    /**
     * FileManager constructor.
     * @param Application $application
     * @throws ConfigurationException
     * @throws \Exception
     */
    public function __construct(Application $application) {
        parent::__construct('FileManager', $application);

        $configuration = $this->getConfiguration();

        if (!$configuration->isset('folder')) {
            throw new ConfigurationException(sprintf("Vous devez spécifier un dossier dans la configuration du service FileManager (relatif au dossier racine de l'application)"));
        }

        $this->folder = Path::build($this->application->getRootPath(), $configuration->get('folder'));

        if (!file_exists($this->folder)) {
            if (!mkdir($this->folder)) {
                throw new \Exception(sprintf("Impossible de créer le dossier '%s' lors de l'initilisation de FileManager", $this->folder));
            }
        }
    }

    /**
     * @param $identifier
     * @return bool|string
     * @throws BundleException
     * @throws FileException
     */
    public function get($identifier) {
        $file_infos = $this->parseIdentifier($identifier);
        if (!file_exists($file_infos['file_path'])) {
            throw new FileException(sprintf("Le fichier '%s:%s' n'existe pas", $identifier));
        }
        return $file_infos['file_path'];
    }

    /**
     * @param $identifier
     * @return bool
     * @throws BundleException
     * @throws FileException
     */
    public function file_exists($identifier) {
        $file_infos = $this->parseIdentifier($identifier);
        return file_exists($file_infos['file_path']);
    }

    /**
     * @param $original_file_path
     * @param $bundle
     * @return string
     * @throws BundleException
     * @throws FileException
     */
    public function copy_file($original_file_path, $bundle, $filename=null) {
        $bundle_path = Path::build($this->folder, $this->bundleToPath($bundle));

        if (!file_exists($original_file_path)) {
            throw new FileException(sprintf("Le fichier '%s' n'existe pas", $original_file_path));
        }

        if ($filename === null) {
            try {
                $filename = Randomizer::generate_unique_token(20, function($value) use ($bundle_path) {
                    return !file_exists(Path::build($bundle_path, $value));
                });
            } catch (\Exception $exception) {
                throw new FileException(sprintf("Impossible de générer l'identifiant du fichier (Error : %s)", $exception->getMessage()));
            }
        } else {
            if (file_exists(Path::build($bundle_path, $filename))) {
                throw new FileException(sprintf("Le fichier nommé '%s' existe déjà", $filename));
            }
        }

        $file_path = Path::build($bundle_path, $filename);

        if (copy($original_file_path, $file_path)) {
            return sprintf("%s:%s", $bundle, $filename);
        } else {
            throw new FileException(sprintf("Impossible de déplacer le fichier '%s'", $original_file_path));
        }
    }

    /**
     * @param $original_file_path
     * @param $bundle
     * @return string
     * @throws BundleException
     * @throws FileException
     */
    public function move_file($original_file_path, $bundle, $filename=null) {
        $bundle_path = Path::build($this->folder, $this->bundleToPath($bundle));

        if (!file_exists($original_file_path)) {
            throw new FileException(sprintf("Le fichier '%s' n'existe pas", $original_file_path));
        }

        if ($filename === null) {
            try {
                $filename = Randomizer::generate_unique_token(20, function($value) use ($bundle_path) {
                    return !file_exists(Path::build($bundle_path, $value));
                });
            } catch (\Exception $exception) {
                throw new FileException(sprintf("Impossible de générer l'identifiant du fichier (Error : %s)", $exception->getMessage()));
            }
        } else {
            if (file_exists(Path::build($bundle_path, $filename))) {
                throw new FileException(sprintf("Le fichier nommé '%s' existe déjà", $filename));
            }
        }

        $file_path = Path::build($bundle_path, $filename);

        if (rename($original_file_path, $file_path)) {
            return sprintf("%s:%s", $bundle, $filename);
        } else {
            throw new FileException(sprintf("Impossible de déplacer le fichier '%s'", $original_file_path));
        }
    }

    /**
     * @param $tmp_name
     * @param $bundle
     * @return string
     * @throws BundleException
     * @throws FileException
     */
    public function move_upload_file($tmp_name, $bundle, $filename=null) {
        $bundle_path = Path::build($this->folder, $this->bundleToPath($bundle));

        if ($filename === null) {
            try {
                $filename = Randomizer::generate_unique_token(10, function($value) use ($bundle_path) {
                    return !file_exists(Path::build($bundle_path, $value));
                });
            } catch (\Exception $exception) {
                throw new FileException(sprintf("Impossible de générer l'identifiant du fichier (Error : %s)", $exception->getMessage()));
            }
        } else {
            if (file_exists(Path::build($bundle_path, $filename))) {
                throw new FileException(sprintf("Le fichier nommé '%s' existe déjà", $filename));
            }
        }

        $file_path = Path::build($bundle_path, $filename);

        if (move_uploaded_file($tmp_name, $file_path)) {
            return sprintf("%s:%s", $bundle, $filename);
        } else {
            throw new FileException(sprintf("Impossible de déplacer le fichier envoyé"));
        }
    }

    /**
     * @param $bundle
     * @return string
     * @throws BundleException
     * @throws FileException
     */
    public function create_file($bundle, $filename=null) {
        $bundle_path = Path::build($this->folder, $this->bundleToPath($bundle));

        if ($filename === null) {
            try {
                $filename = Randomizer::generate_unique_token(20, function($value) use ($bundle_path) {
                    return !file_exists(Path::build($bundle_path, $value));
                });
            } catch (\Exception $exception) {
                throw new FileException(sprintf("Impossible de générer l'identifiant du fichier (Error : %s)", $exception->getMessage()));
            }
        } else {
            if (file_exists(Path::build($bundle_path, $filename))) {
                throw new FileException(sprintf("Le fichier nommé '%s' existe déjà", $filename));
            }
        }

        $file_path = Path::build($bundle_path, $filename);

        if (touch($file_path)) {
            return sprintf("%s:%s", $bundle, $filename);
        } else {
            throw new FileException(sprintf("Impossible de créer le fichier '%s'", $file_path));
        }
    }

    /**
     * @param $bundle
     * @return bool|string
     * @throws BundleException
     */
    private function bundleToPath($bundle) {

        $bundle_parts = explode(':', $bundle);

        $path = "";
        foreach ($bundle_parts as $item) {
            $pattern = "/[\w]/";
            if (!RegExp::matches($pattern, $item)) {
                throw new BundleException(sprintf("'%s' n'est pas un nom de bundle valide", $item));
            }
            $path = Path::build($path, $item);

            $temp = Path::build($this->folder, $path);
            if (!file_exists($temp)) {
                if (!mkdir($temp)) {
                    throw new BundleException(sprintf("Impossible de créer le dossier '%s' lors de l'initilisation du bundle '%s'", $temp, $bundle));
                }
            }
        }
        return $path;
    }

    /**
     * @param $identifier
     * @return array
     * @throws BundleException
     * @throws FileException
     */
    private function parseIdentifier($identifier) {

        $parts = explode(':', $identifier);

        $filename = array_pop($parts);
        $bundle = join(':', $parts);

        if (!ctype_alnum($filename)) {
            throw new FileException(sprintf("'%s' n'est pas un nom de fichier valide", $filename));
        }

        $bundle_path = $this->bundleToPath($bundle);
        $file_path = Path::build($this->folder, $bundle_path, $filename);

        return [
            'filename' => $filename,
            'file_path' => $file_path,
            'bundle' => $bundle,
            'bundle_path' => $bundle_path
        ];
    }

    /**
     * @param $identifier
     * @return string
     */
    static public function filename($identifier) {
        return array_pop(explode(":", $identifier));
    }

    /**
     * @param $identifier
     * @return array
     */
    static public function bundle($identifier) {
        return array_slice(explode(":", $identifier), 0, -1);
    }

}