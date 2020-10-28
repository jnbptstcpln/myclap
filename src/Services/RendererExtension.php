<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 06/08/2019
 * Time: 20:47
 */

namespace myCLAP\Services;


use myCLAP\Modules\ManagerModule\ManagerModule;
use Plexus\Event\ApplicationLoaded;
use Plexus\Event\EventManager;
use Plexus\Service\Listener;
use Plexus\Service\Renderer\RendererWrapperInterface;
use Plexus\Session;
use Plexus\Utils\Text;

class RendererExtension extends Listener {

    public function registrerEventListeners(EventManager $eventManager) {
        $eventManager->addEventListener(ApplicationLoaded::class, function(ApplicationLoaded $event) {

            $renderer = $this->getRenderer();

            // Add some globals
            $renderer->addGlobal('__contact', ['fullname' => $this->application->getEnvironmentVar('contact'), 'email' => $this->application->getEnvironmentVar('contact-email')]);
            $renderer->addGlobal('__host', $this->application->getEnvironmentVar('host'));

            // Add some filters

            $renderer->addFilter('first_letter', function($value) {
                return Text::withoutAccent($value)[0];
            });

            $renderer->addFilter('json_decode', function($value) {
                return json_decode($value, true);
            });

            $renderer->addFunction('back_url', function($fallback="") {
                $urls = Session::flashes('back_route');
                return count($urls) > 0 ? $urls[0]['message'] : $fallback;
            });

            $renderer->addFilter('month_label', function($date) {
                $mapping = [
                    1 => 'janvier',
                    2 => 'février',
                    3 => 'mars',
                    4 => 'avril',
                    5 => 'mai',
                    6 => 'juin',
                    7 => 'juillet',
                    8 => 'aout',
                    9 => 'septembre',
                    10 => 'octobre',
                    11 => 'novembre',
                    12 => 'décembre',
                ];
                $month = intval(date('n', strtotime($date)));
                return $mapping[$month];
            });

            $renderer->addFilter('date_label', function($date) {
                return self::date_label($date);
            });

            $renderer->addFilter('since_label', function($date) {
                return self::since_label($date);
            });

            $renderer->addFilter('views_label', function($date) {
                return self::views_label($date);
            });


            $renderer->addFilter('label', function($value, $name) {
                switch (strtolower($name)) {
                    case 'access':
                        return ManagerModule::CONTENT_ACCESS[intval($value)];
                    default:
                        return "";
                }
            });

            $renderer->addFilter('base64_encode', function($value) {
                return base64_encode($value);
            });

            $renderer->addFilter('markdown', function($value) {
                $value = htmlspecialchars($value);
                $value = nl2br($value);
                $value = Text::replacePattern('/(\*\*|__)([\s\S\n]*?)\1/', function($match, $delimiter, $value) {
                    return Text::format("<strong>{}</strong>", $value);
                }, $value);
                $value = Text::replacePattern('/(\*|_)([\s\S\n]*?)\1/', function($match, $delimiter, $value) {
                    return Text::format("<em>{}</em>", $value);
                }, $value);
                return $value;
            }, ['is_safe' => ['html']]);

        });
    }

    static function date_label($date) {
        $day = intval(date('d', strtotime($date)));
        if ($day == 1) {
            $day = "1er";
        }

        $month_mapping = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'aout',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];
        $month = $month_mapping[intval(date('n', strtotime($date)))];

        $year = date('Y', strtotime($date));

        return Text::format("{} {} {}", $day, $month, $year);
    }

    static function since_label($date) {
        $date = new \DateTime($date);
        $since_start = $date->diff(new \DateTime());

        if ($since_start->y >= 1) {
            $value = $since_start->y;
            return Text::format("Il y a {} an{}", $value, ($value > 1) ? 's' : '' );
        }
        if ($since_start->m >= 1) {
            $value = $since_start->m;
            return Text::format("Il y a {} mois", $value);
        }
        if ($since_start->days >= 7) {
            $value = intval(round($since_start->days / 7));
            return Text::format("Il y a {} semaine{}", $value, ($value > 1) ? 's' : '' );
        }
        if ($since_start->days >= 1) {
            $value = $since_start->days;
            return Text::format("Il y a {} jour{}", $value, ($value > 1) ? 's' : '' );
        }
        if ($since_start->h >= 1) {
            $value = $since_start->h;
            return Text::format("Il y a {} heure{}", $value, ($value > 1) ? 's' : '' );
        }
        if ($since_start->m >= 1) {
            $value = $since_start->h;
            return Text::format("Il y a {} minute{}", $value, ($value > 1) ? 's' : '' );
        }

        return "À l'instant";
    }

    static function views_label($views) {
        $views = intval($views);
        if ($views > 1000000) {
            return Text::format("{}M vues", intval(floor($views / 1000000)));
        }
        if ($views > 1000) {
            return Text::format("{}k vues", intval(floor($views / 1000)));
        }
        return Text::format("{} vue{}", $views, ($views > 1) ? 's' : '');
    }

    /**
     * @return \Plexus\Service\AbstractService|RendererWrapperInterface
     * @throws \Exception
     */
    public function getRenderer() {
        return $this->getContainer()->getService('Renderer');
    }

}