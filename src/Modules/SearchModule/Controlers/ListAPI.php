<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/01/2020
 * Time: 18:59
 */

namespace myCLAP\Modules\SearchModule\Controlers;


use myCLAP\ControlerAPI;
use myCLAP\Modules\SearchModule\SearchModule;

class ListAPI extends ControlerAPI {

    const LIMIT_SEARCH = 20;

    /**
     * @param $value_base64
     * @throws \Exception
     */
    public function search_html($value_base64) {

        $search_value = base64_decode($value_base64);

        if (strlen($search_value) === 0) {
            $this->success([]);
            return;
        }

        $limit =  intval($this->paramGet('limit', 4));
        $offset = intval($this->paramGet('offset', 0));

        // Set a minimum and a maximum to the limit
        $limit = min(self::LIMIT_SEARCH, max(0, $limit));
        // Set a minimum to the offset
        $offset = max(0, $offset);

        $videos = $this->getSearchModule()->perform_search($search_value, $limit, $offset);

        $output = [];
        foreach ($videos as $video) {
            $output[] = $this->getRenderer()->render("@SearchModule/listapi/video.html.twig", [
                'video' => $video
            ]);
        }

        $this->success($output);

    }

    /**
     * @return \Plexus\Module|SearchModule
     * @throws \Exception
     */
    public function getSearchModule() {
        return $this->getContainer()->getModule('SearchModule');
    }

}