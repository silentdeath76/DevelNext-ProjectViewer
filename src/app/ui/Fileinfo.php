<?php
namespace app\ui;

use std;
use framework;
use gui;
use app;

class Fileinfo extends AbstractNode
{

    const LABEL_HEADER_PADDING = 6;
    
    
    public $showed = false;
    private $padding = 8;
    private $buttonScreenXShift = 5;
    
    

    /**
     * @var UXPanel
     */
    protected $container;
    
    /**
     * Controlled button for hide or show panel info 
     * 
     * @var UXFlatButton
     */
    private $controllButton;
    
    /**
     * @var IconFileSelected
     */
    private $iconFileSelected;
    
    /**
     * @var UXLabelEx
     */
    private $filePath;
    
    /**
     * @var UXLabelEx
     */
    private $createdAtLabel;
    
    /**
     * @var UXLabelEx
     */
    private $createdAt;
    
    /**
     * @var UXLabelEx
     */
    private $modifiedAtLabel;
    
    /**
     * @var UXLabelEx
     */
    private $modifiedAt;
    
    /**
     * @var UXLabelEx
     */
    private $fileSizeLabel;
    
    /**
     * @var UXLabelEx
     */
    private $fileSize;
    
    /**
     * @var UXScrollPane
     */
    private $dependencyContainer;
    
    /**
     * @var UXFlowPane
     */
    private $dependencyFlowPane;
    
    
    protected function make ()
    {
        $this->container = new UXPanel();
        $this->container->id = 'fileInfo';
        $this->container->title = Localization::get('ui.sidepanel.fielInfo.title');
        $this->container->titlePosition = 'TOP_CENTER';
        $this->container->x = 6;
        $this->container->minWidth = 232;
        $this->container->anchors = [
            "top" => 80,
            "bottom" => 0
        ];
        
        $this->container->lookup('.panel-title')->topAnchor = -14;
        
        $binder = new EventBinder($this->container);
        $binder->bind("construct", function () {
            $this->container->form->add($this->controllButton);
            $this->controllButton->rightAnchor = 5;
            $this->controllButton->y = $this->container->y + 20;
            
            $this->updateModifiedAt('-');
            $this->updateCreatedAt('-');
            
            if (app()->form("MainForm")->ini->get('panel_file_information_show')) {
                $this->show();
            } else {
                $this->hide();
            }
        });
        
        $this->iconFileSelected = new IconFileSelected();
        $this->container->add($this->iconFileSelected->getNode());
        
        $this->controllButton = new UXFlatButton();
        $this->controllButton->maxWidth = 12;
        $this->controllButton->classes->add('panel-toggle');
        $this->controllButton->graphic = new UXHBox();
        $this->controllButton->graphic->maxWidth = 8;
        $this->controllButton->graphic->maxHeight = 10;
        $this->controllButton->graphic->classes->add("arrow");
        $this->controllButton->on("action", [$this, 'controlledButtonAction']);
        
        
        $this->makeLabel('-', 90, 'filePath');
        $this->filePath->autoSize = false;
        $this->filePath->leftAnchor = $this->filePath->rightAnchor = Fileinfo::LABEL_HEADER_PADDING;


        $this->makeLabel(Localization::get('ui.sidepanel.fielInfo.createdAt'), 110, 'createdAtLabel', true);
        $this->makeLabel('-', 130, 'createdAt');
        $this->createdAtLabel->id = 'createdAtLabel';
        
        
        $this->makeLabel(Localization::get('ui.sidepanel.fielInfo.modifiedAt'), 170, 'modifiedAtLabel', true);
        $this->makeLabel('-', 190, 'modifiedAt');
        $this->modifiedAtLabel->id = 'modifiedAtLabel';
        
        
        $this->makeLabel(Localization::get('ui.sidepanel.fielInfo.fileSize'), 230, 'fileSizeLabel', true);
        $this->makeLabel('-', 250, 'fileSize');
        $this->fileSizeLabel->id = 'fileSizeLabel';
        
        
        $this->dependencyContainer = new UXScrollPane($this->dependencyFlowPane = new UXFlowPane());
        $this->dependencyContainer->hbarPolicy = 'NEVER';
        $this->dependencyContainer->width = $this->container->minWidth - Fileinfo::LABEL_HEADER_PADDING * 2;
        $this->dependencyContainer->x = Fileinfo::LABEL_HEADER_PADDING;
        $this->dependencyContainer->topAnchor = 290;
        $this->dependencyContainer->bottomAnchor = Fileinfo::LABEL_HEADER_PADDING * 2;
        $this->dependencyContainer->fitToWidth = true;
        
        $this->dependencyFlowPane->leftAnchor = $this->dependencyFlowPane->rightAnchor = 0;
        $this->dependencyFlowPane->padding = 3;
        $this->dependencyFlowPane->vgap = $this->dependencyFlowPane->hgap = 5;
        
        $this->container->add($this->dependencyContainer);
    }
    
    private function makeLabel ($text, $position, $varName, $isBold = false)
    {
        $this->{$varName} = new UXLabelEx($text);
        $this->{$varName}->autoSize = true;
        $this->{$varName}->x = Fileinfo::LABEL_HEADER_PADDING;
        $this->{$varName}->width = $this->container->width - Fileinfo::LABEL_HEADER_PADDING * 2;
        $this->{$varName}->y = $position;
        
        $this->container->add($this->{$varName});
    }
    
    public function updateDependecyList ($items)
    {
        $this->dependencyFlowPane->children->clear();
        $this->dependencyFlowPane->children->addAll($items);
    }
    
    public function getWidth ()
    {
        return $this->dependencyContainer->width;
    }
    
    private function controlledButtonAction ()
    {
        if (!$this->showed) {
            $this->show();
            app()->form("MainForm")->ini->set('panel_file_information_show', 1);
        } else {
            $this->hide();
            app()->form("MainForm")->ini->set('panel_file_information_show', 0);
        }
    }
    
    public function show ()
    {
        app()->form("MainForm")->tabPane->rightAnchor = $this->container->width + $this->padding * 2 + 3;
        $this->container->rightAnchor = $this->padding;
        $this->controllButton->graphic->rotate = 180;
        $this->controllButton->rightAnchor = $this->container->minWidth + $this->buttonScreenXShift + 2;
        
        $this->showed = true;
    }
    
    public function hide ()
    {
        app()->form("MainForm")->tabPane->rightAnchor = $this->padding + 3;
        $this->container->rightAnchor -= $this->container->minWidth + $this->padding;
        $this->controllButton->graphic->rotate = 0;
        $this->controllButton->rightAnchor = $this->buttonScreenXShift / 2 - 2;
        
        $this->showed = false;
    }
    
    public function updateFilePath ($text)
    {
        $this->filePath->text = $text;
    }
    
    public function updateFileIcon (AbstractFileSystem $provider, $path)
    {

        if ($provider->isfile($path)) {
            
            $this->iconFileSelected->updateClasses(["file-icon"]);
            $this->iconFileSelected->setSize(52, 68);
            
            switch (fs::ext($path)) {
                case 'zip':
                    $this->iconFileSelected->updateClasses(["zip-icon"]);
                    $this->iconFileSelected->setSize(84, 64);
                    $this->iconFileSelected->updateText("");
                    break;
                case 'php':
                    $this->iconFileSelected->updateText("PHP");
                    break;
                case 'fxml':
                    $this->iconFileSelected->updateText("FXML");
                    break;
                default: $this->iconFileSelected->updateText("");
            }
        } else if ($provider->isDirectory($path)) {
            $this->iconFileSelected->updateClasses(["directory-icon"]);
            $this->iconFileSelected->setSize(84, 64);
            $this->iconFileSelected->updateText("");
        }
    }
    
    public function updateCreatedAt ($text)
    {
        $this->createdAt->text = $text;
    }
    
    public function updateModifiedAt ($text)
    {
        $this->modifiedAt->text = $text;
    }
    
    public function updateFileSize ($text)
    {
        $this->fileSize->text = $text;
    }
    
}