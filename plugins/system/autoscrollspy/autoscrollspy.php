<?php
/*
 * @copyright   (C) 2023 Kevin Olson (kevinsguides.com)
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
*/
defined ('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;
use Joomla\Filter\OutputFilter;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;



class PlgSystemAutoScrollSpy extends CMSPlugin
{
    protected $html;

    public function onContentBeforeDisplay($context, &$article, &$params, $limitstart){

        
        //setup
        $plugin = PluginHelper::getPlugin('system', 'autoscrollspy');
        $this->params = new Registry($plugin->params);

        $enable_scrollspy = $this->params->get('enable_scrollspy', 1);
        $top_is_title = $this->params->get('top_is_title', 1);
        $level1selector = $this->params->get('level1selector', 'h2');
        $level2selector = $this->params->get('level2selector', 'h3');
        $min_count = $this->params->get('min_count', 3);
        $style = $this->params->get('style', 'default');
        $render_location = $this->params->get('render_location', 'module');
        $colors = $this->params->get('colors', 'asscolors-default');

        $app = Factory::getApplication();
        $wam = $app->getDocument()->getWebAssetManager();
        $plugin_path = URI::base().'plugins/system/autoscrollspy';
        $wam->registerAndUseStyle('plg_system_autoscrollspy', $plugin_path.'/assets/default.css', [], ['version' => 'auto']);
        if($enable_scrollspy == 1){
            $wam->registerAndUseScript('plg_system_autoscrollspy', $plugin_path.'/assets/autoscrollspy.js', [], ['defer' => 'true']);
        }
        
        /* floatpanel props*/
        $floatpanel_position = $this->params->get('floatpanel_position', 'left');
        $floatpanel_width = $this->params->get('floatpanel_width', '200px');
        $floatpanel_offset_top = $this->params->get('floatpanel_offset_top', '250px');
        $floatpanel_paneltitle = $this->params->get('floatpanel_paneltitle', '');
        $floatpanel_autocollapse_width = $this->params->get('floatpanel_autocollapse_width', '768');
        $floatpanel_collapse_toggler_type = $this->params->get('floatpanel_collapse_toggler_type', 'fa-button');

        // handle offset of scroll to heading (pass to js)
        $scroll_offset_top = $this->params->get('scroll_offset_top', '0');
        $wam->addInlineScript('
            const scrollOffsetTop = '.$scroll_offset_top.';
        ', ['type' => 'text/javascript']);


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
            $a_class = 'nav-link autoss-local-link';
            $a2_class = 'nav-link autoss-local-link ms-2';
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
        if(ModuleHelper::isEnabled('mod_autoscrollspy') == false && $render_location != 'floatpanel'){
            return;
        }

        //get contents of page

        $article_text = $article->text;
        $count = 0;

        if($top_is_title == 1){
            $count++;
        }

        //count number of $level1selector and $level2selector
        $count += substr_count($article_text, '<'.$level1selector);
        $count += substr_count($article_text, '<'.$level2selector);

        //replace contents of module with a message
        $module = ModuleHelper::getModule('mod_autoscrollspy');

        $headers = array();
        //create an ordered list of headers and subheaders
        if($top_is_title == 1){
            //get title of article
            $pageTitle = $article->title;
            $menuItem = new \stdClass();
            $menuItem->title = $pageTitle;
            $menuItem->alias = OutputFilter::stringUrlUnicodeSlug($pageTitle);
            $menuItem->level = 1;
            $headers[] = $menuItem;
            //add an empty div at top of article with that alias as id
            //$article_text = '<div id="'.$menuItem->alias.'"></div>'.$article_text;
            echo '<div id="'.$menuItem->alias.'"></div>';
        }
        //find all header elements
        preg_match_all('/<'.$level1selector.'(.*?)<\/'.$level1selector.'>|<'.$level2selector.'(.*?)<\/'.$level2selector.'>/', $article_text, $matches);

        $aliases = array();
        foreach($matches[0] as $match){
            //get title
            preg_match('/>(.*?)</', $match, $title);

            $matchAlias = '';
            //check for existing id
            if(strpos($match, 'id=') !== false){
                
                preg_match('/id="(.*?)"/', $match, $id);
                $matchAlias = $id[1];
            }else{
                $matchAlias = OutputFilter::stringUrlUnicodeSlug($title[1]);
            }

            $aliases[] = $matchAlias;
        }

        //find amount of times each alias is used
        $aliasCounts = array_count_values($aliases);

        $replacementOffset = 0;

        $aliasUsage = [];

        //loop through matches
        $index = 0;
        foreach($matches[0] as $match){

            $menuItem = new \stdClass();
            //get title
            preg_match('/>(.*?)</', $match, $title);
            //set title
            $menuItem->title = $title[1];
            $matchAlias = $aliases[$index];
            //set alias
            $menuItem->alias = $matchAlias;


            //get level based off of if it has a $level1selector or $level2selector
            if(strpos($match, '</'.$level1selector) !== false){
                $menuItem->level = 1;
            }else{
                $menuItem->level = 2;
            }

            //if the alias is used more than once, add a number to the end of it
            // if($aliasCounts[$matchAlias] > 1){
            //     //set $menuItem->alias to # of times it has been used
            //     $menuItem->alias = $matchAlias.($aliasCounts[$matchAlias]);
            //     //subtract 1 from $aliasCounts[$matchAlias]
            //     $aliasCounts[$matchAlias]--;
            // }
            if(isset($aliasUsage[$matchAlias])){
                // Increment the usage count
                $aliasUsage[$matchAlias]++;
                // Append the usage count to the alias
                $menuItem->alias = $matchAlias . '-' . $aliasUsage[$matchAlias];
            } else {
                // This is the first time this alias is being used
                $aliasUsage[$matchAlias] = 1;
            }

            //add to array
            $headers[] = $menuItem;

            //replace <h1>title</h1> with <h1 id="alias">title</h1>

           if($menuItem->level == 1){
            $article_text = substr_replace($article_text, '<'.$level1selector.' id="'.$menuItem->alias.'">'.$menuItem->title.'</'.$level1selector.'>', strpos($article_text, $match)+$replacementOffset, strlen($match));
           }
            else{
            $article_text = substr_replace($article_text, '<'.$level2selector.' id="'.$menuItem->alias.'">'.$menuItem->title.'</'.$level2selector.'>', strpos($article_text, $match)+$replacementOffset, strlen($match));
            }

            $index++;
            
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

        }

        $html .= '</'.$list_elem.'>';

        $dataSticky = '';
        $dataStickyParentLevel = '';
        if($render_location == 'modulesticky'){
            $dataSticky = 'data-sticky="true" ';
            $stickyParentLevelValue = $this->params->get('sticky_container_parent_level', 2);
            $dataStickyParentLevel = 'data-sticky-parent-level="'.$stickyParentLevelValue.'" ';
        }

        
        $html = '<div class="'.$colors.'" id="autoScrollSpyContainer"'.$dataSticky.$dataStickyParentLevel.'>'.$html.'</div>';

        //if render location is set to modulesticky, we will try to add sticky-top to the module container class list

        // this no longer works in Joomla 5.
        // if($render_location == 'modulesticky' ){
        //     $modparams = json_decode($module->params);
        //     if(isset($modparams->moduleclass_sfx)){
        //         $modparams->moduleclass_sfx .= ' sticky-top';
        //     }else{
        //     $modparams->moduleclass_sfx = ' sticky-top';
        //     }
        //     $module->params = json_encode($modparams);
        // }


        //if render location is set to left, we will try to place it on the left side of a page in a styled cardlike container
        //and count > min_count
        if($render_location == 'floatpanel' && $count >= $min_count){

            $styles = '';
            $styles .= 'width:'.$floatpanel_width.';';
            $styles .= 'top: '.$floatpanel_offset_top.';';

            if($floatpanel_paneltitle != ''){
                $html = '<h4 class="autoss-paneltitle">'.$floatpanel_paneltitle.'</h4>'.$html;
            }

            //load language constants
            $lang = Factory::getApplication()->getLanguage();
            $lang->load('plg_system_autoscrollspy', JPATH_ADMINISTRATOR);

            $toggletext = Text::_('PLG_SYSTEM_AUTOSCROLLSPY_FLOATPANEL_COLLAPSETOGGLETEXT');

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
                $article_text = $article_text.'<div class="autoss-float-toggle as-float-left '.$colors.$usebtnclass.'" title="'.$toggletext.'">'.$toggleInnerHtml.'</div><div class="autoss-floatcontainer as-float-left '.$colors.'" style="'.$styles.'">'.$html.'</div>';
            }else{
                $article_text = $article_text. '<div class="autoss-float-toggle as-float-right '.$colors.$usebtnclass.'" title="'.$toggletext.'">'.$toggleInnerHtml.'</div><div class="autoss-floatcontainer as-float-right '.$colors.'"  style="'.$styles.'">'.$html.'</div>';
            }
        }

        if ($count < $min_count){
            $html = '';
        }

        $article->text = $article_text;

        //update the module
        Factory::getApplication()->setUserState('autoscrollspy.html', $html);
    }

 



}