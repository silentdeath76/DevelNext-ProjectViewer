<?php
namespace app\modules;

use app\ui\notify\UpdateNotify;
use Exception;
use httpclient;
use std, gui, framework, app;


class AppModule extends AbstractModule
{
    const SELF_UPDATE_DELAY = 10000;

    const APP_VERSION = '1.1.7';
    const APP_TITLE = 'DevelNext ProjectView';
    
    const FOUND_NEW_VERSION = -1;

    const WINDOW_MIN_WIDTH = 900;
    const WINDOW_MIN_HEIGHT = 450;

    /**
     * @var String
     */
    private $temp;

    /**
     * @event construct
     */
    function doConstruct(ScriptEvent $e = null)
    {
        $this->temp = System::getProperty('java.io.tmpdir');
        
        Localization::load('res://.data/local/ru.txt');

        // call garbage collector every 30s
        Timer::every(30000, function () {System::gc(); });
    }

    /**
     * @event action
     */
    function doAction(ScriptEvent $e = null)
    {
        $theme = app()->module("MainModule")->ini->get("theme") ?: 'light';
        // Чтобы форма не мелькала при ресайзе окна
        $form = $this->form("MainForm");
        $form->minWidth = AppModule::WINDOW_MIN_WIDTH;
        $form->minHeight = AppModule::WINDOW_MIN_HEIGHT;
        $form->opacity = 0;
        $form->title = AppModule::APP_TITLE;
        $form->data('theme', $theme);

        $form->show();


        $thread = new Thread(function () use ($form) {
            $file = $this->temp . File::DIRECTORY_SEPARATOR . fs::name($GLOBALS["argv"][0]);

            if (fs::exists($file)) {
                Thread::sleep(1000);
                Logger::info('update from already downloaded file');

                $this->updateAndRun($file);
                return;
            }

            Thread::sleep(AppModule::SELF_UPDATE_DELAY);

            $this->update();
        });

        $thread->setDaemon(true);
        $thread->start();
    }



    public function update (): void
    {
        Logger::info('Checking updates...');

        try {
            $selfUpdate = new Selfupdate('silentdeath76', 'DevelNext-ProjectViewer');
        } catch (Exception $ex) {
            Logger::error($ex->getMessage());
            return;
        }

        try {
            $response = $selfUpdate->getLatest();

            if (str::compare(AppModule::APP_VERSION, $response->getVersion()) <= AppModule::FOUND_NEW_VERSION) {
                $form = app()->form("MainForm");
                Logger::info('Found new version');
                Logger::info(sprintf("Current version %s; new version %s;", AppModule::APP_VERSION, $response->getVersion()));

                $tempFile = MainModule::replaceSeparator($this->temp . '/' . $response->getName());

                $os = str::lower(System::getProperties()["os.name"]);

                // не уверен что небудет проблем с правами и тем что это jar файл по-этому так
                // хз почему, но на линуксе открытие ссылки в браузере вовсе вешает программу
                /* if (str::endsWith($os, 'inux')) {
                    uiLater(function () use ($form) {
                        $this->showUpdateNotify(Localization::get('ui.update.found.message') . '   ', Localization::get('ui.update.button.update'), $form->infoPanelSwitcher, function ()  {
                            browse('https://github.com/silentdeath76/DevelNext-ProjectViewer/releases/latest/');
                        });
                    });
                    
                    return;
                } */

                // $selfUpdate->download($tempFile);

                uiLater(function () use ($form, $response, $tempFile) {
                    $this->showUpdateNotify(
                        Localization::get('ui.update.found.message') . '   ',
                        Localization::get('ui.update.button.update'),
                        $form,
                        function () use ($response, $tempFile) {
                            $thread = new Thread(function () use ($response, $tempFile) {
                                $this->updateAndRun($tempFile);
                            });
                            $thread->setDaemon(true);
                            $thread->start();
                        }
                    );

                    $form->tabPane->toBack();
                });

            }

        } catch (Exception $ex) {
            uiLater(function () use ($ex) {
                app()->form("MainForm")->errorAlert($ex, true);
            });
        } finally {
            unset($selfUpdate);
            unset($response);
        }
    }

    private function moveFile ($from, $to): void
    {
        fs::copy($from, $to);

        if (File::of($to)->exists()) {
            fs::delete($from);
        }
    }

    public function showUpdateNotify ($text, $buttonText, $target, callable $callback, $customPadding = 0): void
    {
        static $notify;
        
        if ($notify == null) {
            $notify = new UpdateNotify();
            $notify->getNode()->topAnchor = 29;
            $notify->getNode()->rightAnchor = 10;
            $target->add($notify->getNode());
        }

        $notify->show($text, $buttonText, $callback, $customPadding);
    }

    /**
     * @param string $file
     */
    private function updateAndRun(string $file): void
    {
        $os = str::lower(System::getProperties()["os.name"]);

        if (str::startsWith($os, 'win')) {
            try {
                $this->moveFile($file, './' . fs::name($GLOBALS["argv"][0]));
                execute(fs::abs('./' . fs::name($GLOBALS["argv"][0])));
            } catch (Exception $ex) {
                Logger::error($ex->getMessage());
            }

            app()->shutdown();
        } else if (str::endsWith($os, 'inux')) {
            Logger::error('Unnsupported operation');
            Logger::info(sprintf('Open path "%s" and copy file "%s.exe" manually', $this->temp, fs::nameNoExt($GLOBALS["argv"][0])));
        }
    }

}
