<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/08/2019
 * Time: 23:59
 */

namespace Plexus;


class ControlerAPI extends Controler {

    /**
     * @param $payload
     * @throws \Exception
     */
    public function success($payload) {
        $this->getRouter()->getResponse()->setStatusCode(200);
        $this->json([
            'status' => 200,
            'success' => true,
            'payload' => $payload
        ]);
    }

    /**
     * @param $code
     * @param $message
     * @throws \Exception
     */
    public function error($code, $message) {
        $this->getRouter()->getResponse()->setStatusCode($code);
        $this->json([
            'status' => $code,
            'success' => false,
            'message' => $message
        ]);
    }

}