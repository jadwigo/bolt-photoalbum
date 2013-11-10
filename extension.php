<?php
// Photo album pager extension
namespace PhotoAlbum;

class Extension extends \Bolt\BaseExtension
{
    protected $config;
    /**
     * Extension metadata
     */
    public function info()
    {
        $data = array(
            'name' =>"Photo album",
            'description' => "Page sequentially through albums with {{ AlbumNext() }} and {{ AlbumPrev() }} and {{ Album() }}.",
            'keywords' => "photo, album, slideshow, gallery",
            'author' => "Lodewijk Evers",
            'link' => "https://github.com/jadwigo/bolt-photoalbum",
            'version' => "0.3",
            'required_bolt_version' => "1.0.2",
            'highest_bolt_version' => "1.3",
            'type' => "General",
            'first_releasedate' => "2013-02-10",
            'latest_releasedate' => "2013-11-10",
            'dependencies' => "",
            'priority' => 10
        );
        return $data;
    }

    /**
     * Register Twig functions
     */
    public function initialize()
    {
        $this->addTwigFunction('AlbumNext', 'twigAlbumNext');
        $this->addTwigFunction('AlbumPrev', 'twigAlbumPrev');
        $this->addTwigFunction('AlbumPhotos', 'twigAlbumAll');
        return true;
    }

    /**
     * Twig function for {{ AlbumNext( photo ) }}
     * loads or displays link to next photo in the album
     * based on the current photo
     */
    function twigAlbumNext($record='', $showlink=true)
    {
        $label = $this->config['labels']['next'];
        $this->next($record);
        if($showlink) {
            $html .= '<a href="'. $record->next->link() .'" title="'. $record->next->values['title'] .'">'. $label ."</a>";
            return new \Twig_Markup($html, 'UTF-8');
        }
    }

    /**
     * Twig function for {{ AlbumPrev( photo ) }}
     * loads or displays link to previous photo in the album
     * based on the current photo
     */
    function twigAlbumPrev($record='', $showlink=true)
    {
        $label = $this->config['labels']['prev'];
        $this->previous($record);
        if($showlink) {
            $html .= '<a href="'. $record->previous->link() .'" title="'. $record->previous->values['title'] .'">'. $label ."</a>";
            return new \Twig_Markup($html, 'UTF-8');
        }
    }

    /**
     * Twig function for {{ AlbumPhotos( album ) }}
     * Loads all photos in an album
     * based on the current album
     */
    function twigAlbumAll(&$record)
    {
        $record->photos = $this->getRelatedPhotos($record);
        //return $record->photos;
    }

    /**
     * Get the previous record.
     */
    public function previous(&$record)
    {
        $record->previous = $this->getAlbumRelated($record, '<');
        return $record->previous;
    }

    /**
     * Get the next record.
     */
    public function next(&$record)
    {
        $record->next = $this->getAlbumRelated($record, '>');
        return $record->next;
    }

    /**
     * Query function to load the precious or next photo
     * from the database
     */
    public function getAlbumRelated($record, $dirlogical='>')
    {
        // Get the contenttype from first $content
        $contenttype = $record->contenttype['slug'];
        $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
        $contenttablename = $prefix . $contenttype;
        $relationtablename = $prefix . "relations";
        $parenttype = $this->config[$contenttype]['relation'];
        $orderfield = safeString($this->config[$contenttype]['order']);
        $direction = isset($this->config[$contenttype]['direction'])?safeString($this->config[$contenttype]['direction']):"ASC";
        if($dirlogical!='>') {
            $dirlogical = '<';
            if($direction=='ASC') {
                $direction='DESC';
            } else {
                $direction='ASC';
            }
        } else {
            $dirlogical = '>';
        }
        $thisrelationvalue = join(',', $record->relation[$parenttype]);
        $currentid = $record->values['id'];
        $thisordervalue = $record->values[$orderfield];

        $query = "SELECT DISTINCT(c.id) FROM $contenttablename c
            JOIN $relationtablename p
            WHERE (
                c.id = p.from_id
                AND p.to_id IN ($thisrelationvalue)
                AND p.to_contenttype = '$parenttype'
                AND p.from_id != $currentid
                AND c.$orderfield $dirlogical $thisordervalue
            ) OR (
                c.id = p.to_id
                AND p.from_id IN ($thisrelationvalue)
                AND p.from_contenttype = '$parenttype'
                AND p.to_id != $currentid
                AND c.$orderfield $dirlogical $thisordervalue
            )
            ORDER BY c.$orderfield $direction
            LIMIT 0, 1";
        $entry = $this->app['db']->fetchAll($query);
        if( $entry[0]['id'] ) {
            $id = array('id' => $entry[0]['id']);
            $relation = $this->app['storage']->getContent($contenttype, $id);
            return $relation;
        }
        return false;
    }

    /**
     * Query function to get all photos in an album
     */
    public function getRelatedPhotos($record, $dirlogical='>')
    {
        // Get the contenttype from first $content
        $parenttype = $record->contenttype['slug'];
        $contenttype = $this->config[$parenttype]['relation'];
        $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');
        $contenttablename = $prefix . $contenttype;
        $relationtablename = $prefix . "relations";
        $orderfield = safeString($this->config[$contenttype]['order']);
        $direction = isset($this->config[$contenttype]['direction'])?safeString($this->config[$contenttype]['direction']):"ASC";
        if($dirlogical!='>') {
            $dirlogical = '<';
            if($direction=='ASC') {
                $direction='DESC';
            } else {
                $direction='ASC';
            }
        } else {
            $dirlogical = '>';
        }
        $currentid = $record->values['id'];
        $thisordervalue = $record->values[$orderfield];
        $query = "SELECT DISTINCT(c.id) as id, c.$orderfield as weight
            FROM $contenttablename c
            JOIN $relationtablename p
            WHERE (
                c.id = p.from_id
                AND p.to_id = $currentid
                AND p.to_contenttype = '$parenttype'
            ) OR (
                c.id = p.to_id
                AND p.from_id = $currentid
                AND p.from_contenttype = '$parenttype'
            )
            ORDER BY c.$orderfield $direction";
        $entries = $this->app['db']->fetchAll($query);
        if( is_array($entries) ) {
            $relations = array();
            foreach($entries as $entry) {
                $id = array('id' => $entry['id']);
                $currententry = $this->app['storage']->getContent($contenttype, $id);
                $relations[ $entry['id'] ] = $currententry;
            }
            return $relations;
        }
        return false;
    }

}
