<?php
/*
 * @copyright   (C) 2023 Kevin Olson (kevinsguides.com)
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
*/
defined ('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;



class PlgSystemAutoScrollSpy extends CMSPlugin
{

    public function onBeforeRender(){

        $params = $this->params;
        $enable_scrollspy = $params->get('enable_scrollspy', 1);
        $top_is_title = $params->get('top_is_title', 1);
        $level1selector = $params->get('level1selector', 'h2');
        $level2selector = $params->get('level2selector', 'h3');
        $min_count = $params->get('min_count', 3);
        $style = $params->get('style', 'default');
        $render_location = $params->get('render_location', 'module');
        $colors = $params->get('colors', 'asscolors-default');

        $app = Factory::getApplication();

        $wam = $app->getDocument()->getWebAssetManager();

        $wam->registerAndUseStyle('plg_system_autoscrollspy', '/plugins/system/autoscrollspy/assets/default.css', [], ['version' => 'auto']);
        
        if($enable_scrollspy == 1){
            $wam->registerAndUseScript('plg_system_autoscrollspy', '/plugins/system/autoscrollspy/assets/autoscrollspy.js', [], ['defer' => 'true']);
        }
        
  
        $floatpanel_position = $params->get('floatpanel_position', 'left');
        $floatpanel_width = $params->get('floatpanel_width', '200px');
        $floatpanel_offset_top = $params->get('floatpanel_offset_top', '250px');
        $floatpanel_paneltitle = $params->get('floatpanel_paneltitle', '');
        $floatpanel_autocollapse_width = $params->get('floatpanel_autocollapse_width', '768');
        $floatpanel_collapse_toggler_type = $params->get('floatpanel_collapse_toggler_type', 'fa-button');

        

        $list_elem = 'ul';
        $li = 'li';
        $list_elem_class = '';
        $a_class = '';
        $a2_class = '';
        if($style == 'ol'){
            $list_elem = 'ol';
        }
        else if($style=='bsnavpill'){
            $list_elem = 'nav';
            $li = 'span';
            $list_elem_class = 'nav nav-pills flex-column';
            $a_class = 'nav-link';
            $a2_class = 'nav-link ms-2';
        }

        //make sure we're on an article page
        if($app->input->get('option') != 'com_content' || $app->input->get('view') != 'article'){
            return;
        }

        //make sure we're on front end/site
        if($app->isClient('administrator')){
            return;
        }


        //check if module is enabled
        if(JModuleHelper::isEnabled('mod_autoscrollspy') == false && $render_location != 'floatpanel'){
            return;
        }
        //get contents of page
        $article = JFactory::getApplication()->getDocument()->getBuffer('component');

        $count = 0;

        if($top_is_title == 1){
            $count++;
        }

        //count number of $level1selector and $level2selector
        $count += substr_count($article, '<'.$level1selector);
        $count += substr_count($article, '<'.$level2selector);

        //make sure count is greater than min_count
        if($count < $min_count){
            return;
        }


        //replace contents of module with a message
        $module = JModuleHelper::getModule('mod_autoscrollspy');

        $headers = array();
        //create an ordered list of headers and subheaders
        if($top_is_title == 1){
            $pageTitle = $app->getDocument()->getTitle();
            $menuItem = new \stdClass();
            $menuItem->title = $pageTitle;
            $menuItem->alias = JFilterOutput::stringURLSafe($pageTitle);
            $menuItem->level = 1;
            $headers[] = $menuItem;
            //add an empty div at top of article with that alias as id
            $article = '<div id="'.$menuItem->alias.'"></div>'.$article;
        }
        //find all header elements
        //preg_match_all('/<'.$level1selector.'(.*?)<\/'.$level1selector.'>/', $article, $matches);
        //find all header elements based off $level1selector and $level2selector in the order they appear

        preg_match_all('/<'.$level1selector.'(.*?)<\/'.$level1selector.'>|<'.$level2selector.'(.*?)<\/'.$level2selector.'>/', $article, $matches);

        //loop through matches
        foreach($matches[0] as $match){
            //create a new object
            $menuItem = new \stdClass();
            //get the title
            preg_match('/>(.*?)</', $match, $title);
            //set the title
            $menuItem->title = $title[1];
            //set the alias
            $menuItem->alias = JFilterOutput::stringURLSafe($title[1]);

            //get level based off of if it has a $level1selector or $level2selector
            if(strpos($match, '</'.$level1selector) !== false){
                $menuItem->level = 1;
            }else{
                $menuItem->level = 2;
            }



            //add to array
            $headers[] = $menuItem;
        }

        //create the html for the module
        //it's an ol with lids and levels
        $html = '<'.$list_elem.' class="autoss-nav '.$list_elem_class.'" data-listelem='.$list_elem.' data-collapsewidth='.$floatpanel_autocollapse_width.'>';

        for($i = 0; $i < count($headers); $i++){
            $header = $headers[$i];
            //if this is a level 2 header
            if($header->level == 2){
                //if the previous header was a level 1 header
                if(isset($headers[$i-1]) && $headers[$i-1]->level == 1){
                    //open the level 2 list
                    $html .= '<'.$list_elem.' class="'.$list_elem_class.'">';
                }
                //add the level 2 header
                $html .= '<'.$li.'><a href="#'.$header->alias.'" class="'.$a2_class.'">'.$header->title.'</a></'.$li.'>';
                //if the next header is a level 1 header
                if(isset($headers[$i+1]) && $headers[$i+1]->level == 1){
                    //close the level 2 list
                    $html .= '</'.$list_elem.'></'.$li.'>';
                }
                //if the next item does not exist
                if(!isset($headers[$i+1])){
                    //close the level 2 list
                    $html .= '</'.$list_elem.'></'.$li.'>';
                }
            }else{

                //add the level 1 header
                $html .= '<'.$li.'><a href="#'.$header->alias.'"  class="'.$a_class.'">'.$header->title.'</a>';
                //if the next header is not a level 2 header
                if(!isset($headers[$i+1]) || $headers[$i+1]->level == 1){
                    //close the level 1 list
                    $html .= '</'.$li.'>';
                }
               

            }
            //
        }

        $html .= '</'.$list_elem.'>';

        $html = '<div class="'.$colors.'">'.$html.'</div>';


        //look through the original matches add missing ids - they should be the alias of that header
        foreach($matches[0] as $match){
            //get the title
            preg_match('/>(.*?)</', $match, $title);
            //set the title
            $title = $title[1];
            //set the alias
            $alias = JFilterOutput::stringURLSafe($title);

            //if the alias is not in the match
            if(strpos($match, $alias) === false){
                //add the alias to the match
                $newmatch = str_replace('>', ' id="'.$alias.'">', $match);
                //replace the match in the article
                $article = str_replace($match, $newmatch, $article);
            }
        }

        





        //if render location is set to modulesticky, we will try to add sticky-top to the module container class list

        if($render_location == 'modulesticky' ){
            $modparams = json_decode($module->params);
            if(isset($modparams->moduleclass_sfx)){
                $modparams->moduleclass_sfx .= ' sticky-top';
            }else{
            $modparams->moduleclass_sfx = ' sticky-top';
            }
            $module->params = json_encode($modparams);
            
        }

        if( $render_location == 'module' || $render_location == 'modulesticky' ){
            //replace the contents of the module with the html
            $module->content = $html;
        }

        //if render location is set to left, we will try to place it on the left side of a page in a styled cardlike container
        if($render_location == 'floatpanel'){

            $styles = '';
            $styles .= 'width:'.$floatpanel_width.';';
            $styles .= 'top: '.$floatpanel_offset_top.';';

            if($floatpanel_paneltitle != ''){
                $html = '<h4 class="autoss-paneltitle">'.$floatpanel_paneltitle.'</h4>'.$html;
            }

            //load language constants
            $lang = JFactory::getLanguage();
            $lang->load('plg_system_autoscrollspy', JPATH_ADMINISTRATOR);

            $toggletext = JText::_('PLG_SYSTEM_AUTOSCROLLSPY_FLOATPANEL_COLLAPSETOGGLETEXT');

            $toggleInnerHtml = $toggletext;



            if($floatpanel_collapse_toggler_type == 'fa-button'){
                $toggleInnerHtml = '<i class="fas fa-ellipsis-v"></i>';
            }
            else if($floatpanel_collapse_toggler_type == 'fa-bars-button'){
                $toggleInnerHtml = '<i class="fas fa-bars"></i>';
            }

            $usebtnclass = '';
            if($colors == 'asscolors-default'){
                $usebtnclass = ' btn btn-primary';
            }

            


            if($floatpanel_position == 'left'){
                $article = $article.'<div class="autoss-float-toggle as-float-left '.$colors.$usebtnclass.'" title="'.$toggletext.'">'.$toggleInnerHtml.'</div><div class="autoss-floatcontainer as-float-left '.$colors.'" style="'.$styles.'">'.$html.'</div>';
            }else{
                $article = $article. '<div class="autoss-float-toggle as-float-right '.$colors.$usebtnclass.'" title="'.$toggletext.'">'.$toggleInnerHtml.'</div><div class="autoss-floatcontainer as-float-right '.$colors.'"  style="'.$styles.'">'.$html.'</div>';
            }
        }


        //update component buffer
        JFactory::getApplication()->getDocument()->setBuffer($article, 'component');



 
    }


}