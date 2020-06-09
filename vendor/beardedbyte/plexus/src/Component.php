<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 15:18
 */

namespace Plexus;


interface Component {

    /**
     * @param array $options
     * @return \Plexus\TemplateReference|string
     */
    public function render($options=[]);

}