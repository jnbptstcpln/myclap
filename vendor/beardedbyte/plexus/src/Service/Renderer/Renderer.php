<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 15:31
 */

namespace Plexus\Service\Renderer;


use Plexus\DataType\Collection;
use Plexus\Utils\Path;
use Plexus\Utils\Text;

class Renderer {

    /**
     * @var
     */
    protected $main_template_folder;

    /**
     * @var Collection
     */
    protected $template_folders;

    /**
     * @var Collection
     */
    protected $globals;

    /**
     * @var Collection
     */
    protected $functions;

    /**
     * @var Collection
     */
    protected $filters;

    /**
     * Renderer constructor.
     * @param $template_folder
     * @throws \Exception
     */
    public function __construct($template_folder) {
        if (!is_dir($template_folder)) {
            throw new \Exception(Text::format("Le dossier de template '{}' n'existe pas.", $template_folder));
        }
        $this->template_folders = $template_folder;
        $this->template_folders = new Collection();
        $this->globals = new Collection();
        $this->functions = new Collection();
        $this->filters = new Collection();
    }

    /**
     * @param $template
     * @param array $data
     * @return string
     * @throws \Throwable
     */
    public function render($template, $data=[]) {

        $path = $this->getTemplatePath($template);

        extract($this->globals->toArray());
        $this->functions->each(function($i, callable $function) {
            
        });
        extract($this->functions->toArray());
        extract($this->filters->toArray());

        if (!empty($data)) {
            extract($data);
        }

        ob_start();
        try {
            ob_start();
            include $path;
            return ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

    }

    /**
     * @param $template
     * @return bool|string
     * @throws \Exception
     */
    private function getTemplatePath($template) {
        $folders = explode(DIRECTORY_SEPARATOR, $template);
        if (strlen($folders[0]) > 0) {
            $folderName = array_shift($folders);
            $template_dir = $this->template_folders->get($folderName);
            $path = Path::build($template_dir, ...$folders);
        } else {
            $path = Path::build($this->main_template_folder, ...$folders);
        }
        if (!file_exists($path)) {
            throw new \Exception(Text::format("Le dossier de template '{}' n'existe pas.", $path));
        }
        return $path;
    }

    /**
     * @param $name
     * @param $path
     * @throws \Exception
     */
    public function addTemplateFolder($name, $path) {
        if (!file_exists($path)) {
            throw new \Exception(Text::format("Le dossier de template '{}' n'existe pas.", $path));
        }
        $this->template_folders->set('@'.$name, $path);
    }

    /**
     * @param $name
     * @param $value
     */
    public function addGlobal($name, $value) {
        $this->globals->set($name, $value);
    }

    /**
     * @param $name
     * @param $function
     */
    public function addFunction($name, $function, $options=[]) {
        $this->functions->set($name, $function);
    }

    /**
     * @param $name
     * @param $function
     */
    public function addFilter($name, $function, $options=[]) {
        $this->filters->set($name, $function);
    }

}