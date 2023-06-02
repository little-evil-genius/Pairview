<?php
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB")){
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// HOOKS
$plugins->add_hook('admin_config_settings_change', 'pairview_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'pairview_settings_peek');
$plugins->add_hook("misc_start", "pairview_misc");
$plugins->add_hook("fetch_wol_activity_end", "pairview_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "pairview_online_location");
if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	$plugins->add_hook("global_start", "pairview_myalerts");
}
 
// Die Informationen, die im Pluginmanager angezeigt werden
function pairview_info(){
	return array(
		"name"		=> "Pärchenübersicht",
		"description"	=> "Pluginbeschreibung",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "2.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function pairview_install(){

    global $db, $cache, $mybb;

    // DATENBANKEN ERSTELLEN
    $db->query("CREATE TABLE ".TABLE_PREFIX."pairs(
        `pid` int(10) NOT NULL AUTO_INCREMENT,
        `category` VARCHAR(255) COLLATE utf8_general_ci NOT NULL,
        `partner_one` int(10) NOT NULL,
        `pic_one` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
        `partner_two` int(10) NOT NULL,
        `pic_two` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
        PRIMARY KEY(`pid`),
        KEY `pid` (`pid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1    "
    );

    // VERZEICHNIS ERSTELLEN
    if (!is_dir(MYBB_ROOT.'uploads/pairview')) {
        mkdir(MYBB_ROOT.'uploads/pairview', 0777, true);
    }
    
    // EINSTELLUNGEN HINZUFÜGEN
    $maxdisporder = $db->fetch_field($db->query("SELECT MAX(disporder) FROM ".TABLE_PREFIX."settinggroups"), "MAX(disporder)");
	$setting_group = array(
		'name'          => 'pairview',
		'title'         => 'Pärchenübersicht',
		'description'   => 'Einstellungen für die Pärchenübersicht',
		'disporder'     => $maxdisporder+1,
		'isdefault'     => 0
	);
			
	$gid = $db->insert_query("settinggroups", $setting_group); 

    $setting_array = array(
		'pairview_allowed_groups' => array(
			'title' => 'Erlaubte Gruppen',
			'description' => 'Welche Gruppen dürfen sich in die Pärchenübersicht eintragen?',
			'optionscode' => 'groupselect',
			'value' => '4', // Default
			'disporder' => 1
		),
        'pairview_category' => array(
            'title' => 'Kategorie',
            'description' => 'In welche Kategorien sollen sich die Pärchen eingetragen können?',
            'optionscode' => 'text',
            'value' => 'Verheiratet, Verlobt, Beziehung, Affäre, Zukünftig, Vergangenheit', // Default
            'disporder' => 2
        ),
        'pairview_iconguest' => array(
            'title' => 'Icon ausblenden',
            'description' => 'Sollen die Icons für Gäste ausgeblendet werden und das angegebene Standard-Icon angezeigt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 3
        ),
        'pairview_defaulticon' => array(
            'title' => 'Standard-Icon',
            'description' => 'Wie heißt die Bilddatei, für die Standard-Icons? Damit die Icons für jedes Design angepasst werden, sollte der Namen in allen Designs gleich sein.',
            'optionscode' => 'text',
            'value' => 'default_pairview.png', // Default
            'disporder' => 4
        ),
		'pairview_uploadsystem' => array(
			'title' => 'Upload-System',
			'description' => 'Sollen die Icons über eine Upload-Funktion (zB Avatar Funktion) hochgeladen/gespeichert werden oder sollen die Icons per externen Link eingebunden werden?',
			'optionscode' => 'select\n0=Upload Funktion\n1=externe Links',
			'value' => '0', // Default
			'disporder' => 5
		),
		'pairview_allowed_extensions' => array(
			'title' => 'Erlaubte Dateitypen',
			'description' => 'Welche Dateitypen sind für die Icons erlaubt?',
			'optionscode' => 'text',
			'value' => 'png, jpg, jpeg, gif', // Default
			'disporder' => 6
		),
        'pairview_icondims' => array(
            'title' => 'Icon-Größe',
            'description' => "Die zulässige Größe für die Icons, Breite und Höhe getrennt durch x oder |. Wenn das Feld leer bleibt, wird die Größe nicht beschränkt. Icons können per CSS später entsprechend skaliert werden.",
            'optionscode' => 'text',
            'value' => '90x90', // Default
            'disporder' => 7
        ),
        'pairview_iconsquare' => array(
            'title' => 'Quadratische Icons',
            'description' => 'Müssen die Icons quadratisch sein? Besonders wichtig, wenn keine maximale Größe eingetragen wurde.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 8
        ),
        'pairview_iconsize' => array(
            'title' => 'Maximale Datei-Größe',
            'description' => 'Die maximale Dateigröße (in Kilobyte) für hochgeladene Icons. Der Defaultwert beträgt 5 MB.',
            'optionscode' => 'text',
            'value' => '5120', // Default
            'disporder' => 9
        ),
        'pairview_lists' => array(
            'title' => 'Listen PHP',
            'description' => 'Wie heißt die Hauptseite der Listen-Seite? Dies dient zur Ergänzung der Navigation. Falls nicht gewünscht einfach leer lassen.',
            'optionscode' => 'text',
            'value' => 'lists.php', // Default
            'disporder' => 10
        ),
		'pairview_lists_type' => array(
			'title' => 'Listen Menü',
			'description' => 'Soll über die Variable {$lists_menu} das Menü der Listen aufgerufen werden?<br>Wenn ja, muss noch angegeben werden, ob eine eigene PHP-Datei oder das Automatische Listen-Plugin von sparks fly genutzt?',
			'optionscode' => 'select\n0=eigene Listen/PHP-Datei\n1=Automatische Listen-Plugin\n2=keine Menü-Anzeige',
			'value' => '2', // Default
			'disporder' => 11
		),
        'pairview_lists_menu' => array(
            'title' => 'Listen Menü Template',
            'description' => 'Damit das Listen Menü richtig angezeigt werden kann, muss hier einmal der Name von dem Tpl von dem Listen-Menü angegeben werden.',
            'optionscode' => 'text',
            'value' => 'lists_nav', // Default
            'disporder' => 12
        ),
	);
			
	foreach($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid']  = $gid;
		$db->insert_query('settings', $setting);
	}
	rebuild_settings();

	// TEMPLATES ERSTELLEN
	// Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "pairview",
        "title" => $db->escape_string("Pärchenübersicht"),
    );

    $db->insert_query("templategroups", $templategroup);

    // TEMPLATES HINZUFÜGEN
    $insert_array = array(
        'title'		=> 'pairview',
        'template'	=> $db->escape_string('<html>
        <head>
           <title>{$mybb->settings[\'bbname\']} - {$lang->pairview_main}</title>
           {$headerinclude}
        </head>
        <body>
           {$header}
           <table width="100%" cellspacing="5" cellpadding="0">
              <tr>
                 <td valign="top">
                    <div id="pairview_lists">
                       {$lists_menu}
                       <div class="pairview_lists-body">
                          <div class="pairview_lists-headline">{$lang->pairview_main}</div>
						   {$pairview_error}
                          <div class="pairview_lists-description">{$lang->pairview_main_desc}</div>
                           <div class="pairview_lists-content">
                               {$pairview_categories}
                           </div>
                          {$pairview_add}
                       </div>
                    </div>
                 </td>
              </tr>
           </table>
           {$footer}
        </body>
     </html>'),
        'sid'		=> '-2',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    
    $insert_array = array(
        'title' => 'pairview_add_link',
        'template' => $db->escape_string('<div class="thead">{$lang->pairview_add_nav}</div>
            <div class="pairview_lists-description">{$lang->pairview_add_desc}</div>
            <form method="post" action="misc.php?action=pairview" id="new_pairs" enctype="multipart/form-data">
      <div class="pairviewAdd">
          <div class="pairviewAdd_bit">
              <div class="pairviewAdd_headline">{$lang->pairview_add_type}</div>
              <div class="pairviewAdd_trow" style="border-left: none;">
                  <select name="category">
                      <option value="">{$lang->pairview_add_type_select}</option>
                      {$category_select}
                  </select>
              </div>
        </div>
          <div class="pairviewAdd_bit">
              <div class="pairviewAdd_headline">{$lang->pairview_add_partner_one}</div>
              <div class="pairviewAdd_trow">
                  <div class="pairviewAdd_own">{$own_character}</div>
                   <input type="text" name="pic_one" placeholder="{$lang->pairview_add_pic_one}" class="textbox" style="width:95%">
              </div>
        </div>
          <div class="pairviewAdd_bit">
              <div class="pairviewAdd_headline">{$lang->pairview_add_partner_two}</div>
              <div class="pairviewAdd_trow">
                  <input type="text" class="textbox" name="partner_two" id="partner_two" style="width: 100%;margin:5px" />
                   <input type="text" name="pic_two" placeholder="{$lang->pairview_add_pic_two}" class="textbox" style="width:95%">
              </div>
        </div>
        </div>
        <div class="pairviewAdd_button">
        <input type="hidden" name="action" value="new_pair">
        <input type="submit" value="{$lang->pairview_add_nav}" name="new_pairs" class="button">
        </div>
        </form>
        <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
        <script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
        <script type="text/javascript">
        <!--
        if(use_xmlhttprequest == "1")
        {
            MyBB.select2();
            $("#partner_two").select2({
                placeholder: "{$lang->search_user}",
                minimumInputLength: 2,
                multiple: false,
                allowClear: true,
                ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
                    url: "xmlhttp.php?action=get_users",
                    dataType: \'json\',
                    data: function (term, page) {
                        return {
                            query: term, // search term
                        };
                    },
                    results: function (data, page) { // parse the results into the format expected by Select2.
                        // since we are using custom formatting functions we do not need to alter remote JSON data
                        return {results: data};
                    }
                },
                initSelection: function(element, callback) {
                    var value = $(element).val();
                    if (value !== "") {
                        callback({
                            id: value,
                            text: value
                        });
                    }
                },
                // Allow the user entered text to be selected as well
                createSearchChoice:function(term, data) {
                    if ( $(data).filter( function() {
                        return this.text.localeCompare(term)===0;
                    }).length===0) {
                        return {id:term, text:term};
                    }
                },
            });
            $(\'[for=username]\').on(\'click\', function(){
                $("#username").select2(\'open\');
                return false;
            });
        }
        // -->
        </script>'),
    
        'sid' => '-2',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    
    $insert_array = array(
        'title' => 'pairview_add_upload',
        'template' => $db->escape_string('<div class="thead">{$lang->pairview_add_nav}</div>
        <div class="pairview_lists-description">{$lang->pairview_add_desc}</div>
        <form method="post" action="misc.php?action=pairview" id="new_pairs" enctype="multipart/form-data">
        <div class="pairviewAdd">
      <div class="pairviewAdd_bit">
		  <div class="pairviewAdd_headline">{$lang->pairview_add_type}</div>     
		  <div class="pairviewAdd_trow" style="border-left: none;">		  
			  <select name="category">			  
				  <option value="">{$lang->pairview_add_type_select}</option>			  
				  {$category_select}    
			  </select> 
		  </div>
          </div>
          <div class="pairviewAdd_bit">
          <div class="pairviewAdd_headline">{$lang->pairview_add_partner_one}</div>
          <div class="pairviewAdd_trow">		  
          <div class="pairviewAdd_own">{$own_character}</div>
          <input type="file" name="pic_one">
          <span class="pairviewAdd_iconsize">{$icondims}<br>{$iconsize}</span> 
          </div>
          </div>
          <div class="pairviewAdd_bit">
          <div class="pairviewAdd_headline">{$lang->pairview_add_partner_two}</div>
          <div class="pairviewAdd_trow">		  
          <input type="text" class="textbox" name="partner_two" id="partner_two" style="width: 100%;margin:5px" />
          <input type="file" name="pic_two">
          <span class="pairviewAdd_iconsize">{$icondims}<br>{$iconsize}</span>	  
          </div>
          </div>
          </div>
          <div class="pairviewAdd_button">
          <input type="hidden" name="action" value="new_pair">         
          <input type="submit" value="{$lang->pairview_add_nav}" name="new_pairs" class="button">
          </div>
          </form>
          <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
          <script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
          <script type="text/javascript">
          <!--
          if(use_xmlhttprequest == "1")
          {
            MyBB.select2();
            $("#partner_two").select2({
                placeholder: "{$lang->search_user}",
                minimumInputLength: 2,
                multiple: false,
                allowClear: true,
                ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
                    url: "xmlhttp.php?action=get_users",
                    dataType: \'json\',
                    data: function (term, page) {
                        return {
                            query: term, // search term
                        };
                    },
                    results: function (data, page) { // parse the results into the format expected by Select2.
                        // since we are using custom formatting functions we do not need to alter remote JSON data
                        return {results: data};
                    }
                },
                initSelection: function(element, callback) {
                    var value = $(element).val();
                    if (value !== "") {
                        callback({
                            id: value,
                            text: value
                        });
                    }
                },
                // Allow the user entered text to be selected as well
                createSearchChoice:function(term, data) {
                    if ( $(data).filter( function() {
                        return this.text.localeCompare(term)===0;
                    }).length===0) {
                        return {id:term, text:term};
                    }
                },
            });
            $(\'[for=username]\').on(\'click\', function(){
                $("#username").select2(\'open\');
                return false;
            });
        }
        // -->
        </script>'),    
        'sid' => '-2',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'		=> 'pairview_category',
        'template'	=> $db->escape_string('<div class="pairview">
        <div class="pairview_cathead">{$cat}</div>
        <div class="pairview_pair">
            {$pair_bit}
        </div>
        </div>'),
        'sid'		=> '-2',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'		=> 'pairview_edit_link',
        'template'	=> $db->escape_string('<html>
        <head>
           <title>{$mybb->settings[\'bbname\']} - {$lang->pairview_edit}</title>
           {$headerinclude}
        </head>
        <body>
           {$header}
           <table width="100%" cellspacing="5" cellpadding="0">
              <tr>
                 <td valign="top">
                    <div id="pairview_lists">
                       {$lists_menu}
                       <div class="pairview_lists-body">
                          <div class="pairview_lists-headline">{$lang->pairview_edit}</div>
                          {$pairview_edit_error}
                          <div class="pairview_lists-content">
                              <form  action="misc.php?action=pairview_edit_do&pairID={$pid}" method="post" style="width:100%">
                                <div class="pairviewEdit">
                                   <div class="pairviewEdit_bit">
                                      <div class="pairviewEdit_headline">{$lang->pairview_edit_type}</div>
                                      <div class="pairviewEdit_trow" style="border-left: none;">
                                         <select name="category">
                                            {$category_select}    
                                         </select>
                                      </div>
                                   </div>
                                   <div class="pairviewEdit_bit">
                                      <div class="pairviewEdit_headline">{$lang->pairview_edit_partner_one}</div>
                                      <div class="pairviewEdit_trow">	
                                          <div class="pairviewEdit_avatar">	
                                              <img src="{$pic_one}">
                                          </div>
                                          <div class="pairviewEdit_infos">	                                   
                                              <div class="pairviewEdit_name">{$username_one}</div>
                                              <input type="text" name="pic_one" placeholder="{$lang->pairview_add_pic_one}" class="textbox">                          
                                          </div>
                                      </div>
                                   </div>
                                   <div class="pairviewEdit_bit">
                                      <div class="pairviewEdit_headline">{$lang->pairview_edit_partner_two}</div>
                                      <div class="pairviewEdit_trow">	
                                          <div class="pairviewEdit_avatar">
                                              <img src="{$pic_two}">
                                          </div>
                                          <div class="pairviewEdit_infos">	                                   
                                              <div class="pairviewEdit_name">{$username_two}</div>
                                              <input type="text" name="pic_two" placeholder="{$lang->pairview_add_pic_two}" class="textbox">       
                                          </div>
                                      </div>
                                   </div>
                                </div>
                                <div class="pairviewEdit_button">
                                    <input type="hidden" name="pairID" id="pairID" value="{$pid}" />
                                    <input type="submit" name="pairview_edit_do" value="{$lang->pairview_edit_button}" class="button" />
                                </div>
                             </form>
                          </div>
                       </div>
                    </div>
                 </td>
              </tr>
           </table>
           {$footer}
        </body>
     </html>'),
        'sid'		=> '-2',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'		=> 'pairview_edit_upload',
        'template'	=> $db->escape_string('<html>
        <head>
           <title>{$mybb->settings[\'bbname\']} - {$lang->pairview_edit}</title>
           {$headerinclude}
        </head>
        <body>
           {$header}
           <table width="100%" cellspacing="5" cellpadding="0">
              <tr>
                 <td valign="top">
                    <div id="pairview_lists">
                       {$lists_menu}
                       <div class="pairview_lists-body">
                          <div class="pairview_lists-headline">{$lang->pairview_edit}</div>
                          {$pairview_edit_error}
                          <div class="pairview_lists-content">
                             <form  action="misc.php?action=pairview_edit_do&pairID={$pid}" method="post" style="width:100%" enctype="multipart/form-data">
                                <div class="pairviewEdit">
                                   <div class="pairviewEdit_bit">
                                      <div class="pairviewEdit_headline">{$lang->pairview_edit_type}</div>
                                      <div class="pairviewEdit_trow" style="border-left: none;">
                                         <select name="category">
                                            {$category_select}    
                                         </select>
                                      </div>
                                   </div>
                                   <div class="pairviewEdit_bit">
                                      <div class="pairviewEdit_headline">{$lang->pairview_edit_partner_one}</div>
                                      <div class="pairviewEdit_trow">	
                                          <div class="pairviewEdit_avatar">	
                                              <img src="{$pic_one}">
                                          </div>
                                          <div class="pairviewEdit_infos">	                                   
                                              <div class="pairviewEdit_name">{$username_one}</div>
                                              <input type="file" name="pic_one">                                    
                                              <span class="pairviewEdit_iconsize">{$icondims}<br>{$iconsize}</span> 
                                          </div>
                                      </div>
                                   </div>
                                   <div class="pairviewEdit_bit">
                                      <div class="pairviewEdit_headline">{$lang->pairview_edit_partner_two}</div>
                                      <div class="pairviewEdit_trow">	
                                          <div class="pairviewEdit_avatar">
                                              <img src="{$pic_two}">
                                          </div>
                                          <div class="pairviewEdit_infos">	                                   
                                              <div class="pairviewEdit_name">{$username_two}</div>                                  
                                              <input type="file" name="pic_two">
                                              <span class="pairviewEdit_iconsize">{$icondims}<br>{$iconsize}</span>	  
                                          </div>
                                      </div>
                                   </div>
                                </div>
                                <div class="pairviewEdit_button">
                                    <input type="hidden" name="pairID" id="pairID" value="{$pid}" />
                                    <input type="submit" name="pairview_edit_do" value="{$lang->pairview_edit_button}" class="button" />
                                </div>
                             </form>
                          </div>
                       </div>
                    </div>
                 </td>
              </tr>
           </table>
           {$footer}
        </body>
     </html>'),
        'sid'		=> '-2',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'		=> 'pairview_pair',
        'template'	=> $db->escape_string('<div class="pairview_pairbit">
        <div class="pairpartner">
            <img src="{$pic_one}" class="pairpic">
        </div>
        <div class="pairbit_infos">
            <div class="pairbit_name">{$partner_one}</div>
            <div class="pairbit_name">{$partner_two}</div>
            {$pair_options}
        </div>
        <div class="pairpartner">
            <img src="{$pic_two}" class="pairpic">
        </div>	
        </div>'),
        'sid'		=> '-2',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // STYLESHEET HINZUFÜGEN
    $css = array(
        'name' => 'pairview.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" => '/* LISTEN BODY */
        #pairview_lists {
            width: 100%;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        #pairview_lists .pairview_lists-body {
            width: 100%;
            box-sizing: border-box;
        }
        
        #pairview_lists .pairview_lists-body .pairview_lists-headline {
            height: 50px;
            width: 100%;
            font-size: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            text-transform: uppercase;
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            font-family: Tahoma, Verdana, Arial, Sans-Serif;
            letter-spacing: 1px;
        }
        
        #pairview_lists .pairview_lists-body .pairview_lists-list,
        #pairview_lists .pairview_lists-body .pairview_lists-description {
            text-align: justify;
            line-height: 180%;
            padding: 20px 40px;
        }
        
        
        #pairview_lists .pairview_lists-body .pairview_lists-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
        }
        
        .pairview {
            width: 49%;
        }
        
        .pairview_cathead {
            text-transform: uppercase;
            text-align: center;
            box-sizing: border-box;
            font-family: Tahoma, Verdana, Arial, Sans-Serif;
            letter-spacing: 1px;
            font-size: 25px;
            padding: 5px;   
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
        }
        
        .pairview_pairbit {
            display: flex;
            margin: 5px 0;
            align-items: center;
            justify-content: space-between;
            align-content: center;
        }
        
        .pairpartner {
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
        }
        
        .pairpic {
            width: 100px;
            height: 100px;
            border: 2px solid;
            border-color: #0066a2;
        }
        
        .pairbit_infos {
            display: flex;
            flex-wrap: wrap;
            align-content: center;
            align-items: center;
            justify-content: center;
        }
        
        .pairbit_infos:before {
            font-family: Tahoma, Verdana, Arial, Sans-Serif;
            content: "&";
            opacity: 0.3;
            font-size: 100px;
            position: absolute;
            color:#0f0f0f;
            text-transform: uppercase;
        }
        
        .pairbit_name {
            font-family: Tahoma, Verdana, Arial, Sans-Serif;
            letter-spacing: 1px;
            font-size: 15px;
            width: 100%;
            text-align: center;
            z-index: 2;
        }
        
        .pairbit_name a:link,
        .pairbit_name a:visited,
        .pairbit_name a:active,
        .pairbit_name a:hover {
            color: #0072BC;
            text-decoration: none;
        }
        
        .pairbit_options {
            z-index: 2;
        }
        
        .pairbit_options a:link,
        .pairbit_options a:visited,
        .pairbit_options a:active,
        .pairbit_options a:hover {
            color: #0072BC;
            text-decoration: none;
        }
        
        /* HINZUFUEGN */
        
        .pairviewAdd {
            background: #fff;
            width: 100%;
            margin: auto auto;
            border: 1px solid #ccc;
            /* padding: 1px; */
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-start;
            border-bottom: none;
        }
        
        .pairviewAdd_bit {
            width: 34%;
        }
        
        .pairviewAdd_headline {
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }
        
        .pairviewAdd_trow {
            background: #efefef;
            border: 1px solid;
            border-color: #fff #ddd #ddd #fff;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            height: 90px;
        }
        
        .pairviewAdd_iconsize {
            width: 100%;
            text-align: center;
            font-size: 11px;
        }
        
        .pairviewAdd_own {
            height: 25px;
            color: #333;
            border: none;
            margin: 5px;
            font-size: 17px;
            outline: 0;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
            padding-top: 3px;
        }
        
        .pairviewAdd_button {
            text-align: center;
            background: #efefef;
            border: 1px solid;
            width: 100%;
            margin: auto auto;
            border: 1px solid #ccc;
            border-top: none;
            padding: 10px 0;
        }
        
        /* BEARBEITEN */
        
        
        .pairviewEdit {
            background: #fff;
            width: 100%;
            margin: auto auto;
            border: 1px solid #ccc;
            border-top: none;
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-start;
            border-bottom: none;
        }
        
        .pairviewEdit_bit {
            width: 34%;
        }
        
        .pairviewEdit_headline {
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }
        
        .pairviewEdit_trow {
            background: #efefef;
            border: 1px solid;
            border-color: #fff #ddd #ddd #fff;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            height: 125px;
            gap: 10px;
        }
        
        .pairviewEdit_avatar {
            width: 30%;
            text-align: center;
        }
        
        .pairviewEdit_avatar img {
            width: 100px;
            height: 100px;
            border: 2px solid;
            border-color: #0066a2;
        }
        
        .pairviewEdit_infos {
            width: 67%;
            text-align: center;
        }
        
        .pairviewEdit_infos input[type="file"] {
            width: 100%;
        }
        
        .pairviewEdit_infos input.textbox {
            width: 90%;
        }
        
        .pairviewEdit_iconsize {
            width: 100%;
            text-align: center;
            font-size: 11px;
        }
        
        .pairviewEdit_name {
            color: #333;
            border: none;
            margin: 5px 0 10px;
            font-size: 20px;
            outline: 0;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }
        
        .pairviewEdit_button {
            text-align: center;
            background: #efefef;
            border: 1px solid;
            width: 100%;
            margin: auto auto;
            border: 1px solid #ccc;
            border-top: none;
            padding: 10px 0;
        }',
        'cachefile' => $db->escape_string(str_replace('/', '', 'pairview.css')),
        'lastmodified' => time()
    );
    
    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "pairview.css"), "sid = '".$sid."'", 1);

    $tids = $db->simple_select("themes", "tid");
    while($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }
    
}

// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function pairview_is_installed(){

    global $db, $mybb;

    if ($db->table_exists("pairs")) {
        return true;
    }
    return false;
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function pairview_uninstall(){
  
	global $db;

    //DATENBANK LÖSCHEN
    if($db->table_exists("pairs"))
    {
        $db->drop_table("pairs");
    }
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'pairview%'");
    $db->delete_query('settinggroups', "name = 'pairview'");

    rebuild_settings();

    // TEMPLATGRUPPE LÖSCHEN
    $db->delete_query("templategroups", "prefix = 'pairview'");

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'pairview%'");

    // VERZEICHNIS LÖSCHEN
    rmdir(MYBB_ROOT.'uploads/pairview');

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // STYLESHEET ENTFERNEN
	$db->delete_query("themestylesheets", "name = 'pairview.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}

} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function pairview_activate(){

	global $db, $cache;

    // MyALERTS STUFF
	if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

        // Alert fürs hinzufügen
		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('pairview_add'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        // Alert fürs Bearbeiten
		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('pairview_edit'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        // Alert fürs Löschen
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('pairview_delete'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    }
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function pairview_deactivate(){

	global $db, $cache;

    // MyALERT STUFF
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

        $alertTypeManager->deleteByCode('pairview_add');
		$alertTypeManager->deleteByCode('pairview_edit');
        $alertTypeManager->deleteByCode('pairview_delete');
	}

}

#####################################
### THE BIG MAGIC - THE FUNCTIONS ###
#####################################

// ADMIN-CP PEEKER
function pairview_settings_change(){
    
    global $db, $mybb, $pairview_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='pairview'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $pairview_settings_peeker = ($mybb->input['gid'] == $group['gid']) && ($mybb->request_method != 'post');
}
function pairview_settings_peek(&$peekers){
    global $mybb, $pairview_settings_peeker;

	if ($pairview_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_pairview_iconguest"), $("#row_setting_pairview_defaulticon"),/1/,true)';
     }

	if ($pairview_settings_peeker) {
       $peekers[] = 'new Peeker($("#setting_pairview_uploadsystem"), $("#row_setting_pairview_icondims, #row_setting_pairview_iconsquare, #row_setting_pairview_iconsize, #row_setting_pairview_allowed_extensions"), /^0/, false)';
    }

	if ($pairview_settings_peeker) {
       $peekers[] = 'new Peeker($("#setting_pairview_lists_type"), $("#row_setting_pairview_lists_menu"), /^0/, false)';
    }
}

// DIE SEITEN
function pairview_misc() {

    global $db, $cache, $mybb, $lang, $page, $templates, $theme, $header, $headerinclude, $footer, $pairview_categories, $lists_menu;

    // SPRACHDATEI LADEN
    $lang->load('pairview');
    
    // USER-ID
    $user_id = $mybb->user['uid'];

    // EINSTELLUNGEN ZIEHEN
    $pairview_groups_setting = $mybb->settings['pairview_allowed_groups'];
    $pairview_icon_guest_setting = $mybb->settings['pairview_iconguest'];
    $pairview_defaulticon_setting = $mybb->settings['pairview_defaulticon'];
    $pairview_category_setting = $mybb->settings['pairview_category'];
    $category_string = str_replace(", ", ",", $pairview_category_setting);
    $pairview_category = explode (",", $category_string);

    $pairview_uploadsystem = $mybb->settings['pairview_uploadsystem'];
    $pairview_extensions = $mybb->settings['pairview_allowed_extensions'];
    $extensions_string = str_replace(", ", ",", $pairview_extensions);
    $allowed_extensions = explode (",", $extensions_string);
    $pairview_icondims = $mybb->settings['pairview_icondims'];
    $pairview_iconsquare = $mybb->settings['pairview_iconsquare'];
    $pairview_iconsize = $mybb->settings['pairview_iconsize'];

    $listsnav_setting = $mybb->settings['pairview_lists']; 
    $liststype_setting = $mybb->settings['pairview_lists_type']; 
	$listsmenu_setting = $mybb->settings['pairview_lists_menu']; 

    // ACTION-BAUM BAUEN
    $mybb->input['action'] = $mybb->get_input('action');

    // PÄRCHEN HINZUFÜGEN
    if($mybb->input['action'] == "new_pair") {

        $pairview_error = array();
    
        // USER-ID
        $user_id = $mybb->user['uid'];

        // AUTO ID
        $pairid = pairview_getNextId("pairs");

        $category = $db->escape_string($mybb->get_input('category'));
        if(empty($category)) {
            $pairview_error[] = $lang->pairview_error_category;   
        }

        $partner_uid = $db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE username = '".$mybb->get_input('partner_two')."'"), "uid");
        if(empty($mybb->get_input('partner_two'))) {
            $pairview_error[] = $lang->pairview_error_partner;   
        }

        // Zählen ob diese Variante schon eingetragen ist
        $this_variant = $db->query("SELECT * FROM ".TABLE_PREFIX."pairs 
        WHERE category = '".$category."'      
        AND partner_one = '".$user_id."'
        AND partner_two = '".$partner_uid."' 
        ");
        $count_this = $db->num_rows($this_variant);

        // Zählen ob anders rum schon eingetragen ist
        $different_variant = $db->query("SELECT * FROM ".TABLE_PREFIX."pairs 
        WHERE category = '".$category."'
        AND partner_one = '".$partner_uid."'
        AND partner_two = '".$user_id."' 
        ");
        $count_different = $db->num_rows($different_variant);

        if($count_this > '0' OR $count_different > '0'){
            $pairview_error[] = $lang->pairview_error_double;    
        }

        // ICONS
        // Upload Funktion
        if ($pairview_uploadsystem == 0) {

            require_once MYBB_ROOT."inc/functions_upload.php";
            require_once MYBB_ROOT."inc/functions.php";

            // Verzeichnis für die Icons
            $folder_path =  MYBB_ROOT."uploads/pairview/"; 

            // Dateityp ermittel (.png, .jpg, .gif)
            $imageFileType_picOne = end((explode(".", $_FILES['pic_one']['name'])));
            $imageFileType_picTwo = end((explode(".", $_FILES['pic_two']['name'])));

            // Bildname - Speichern
            $filename_picOne = 'pair'.$pairid.'_'.$user_id.'.' . $imageFileType_picOne;
            $filename_picTwo = 'pair'.$pairid.'_'.$partner_uid.'.' . $imageFileType_picTwo;

            // Hochladen
            move_uploaded_file($_FILES['pic_one']['tmp_name'], $folder_path . $filename_picOne);
            move_uploaded_file($_FILES['pic_two']['tmp_name'], $folder_path . $filename_picTwo);
            
            // Grafik-Größe
            $imgDimensions_picOne = @getimagesize($folder_path . $filename_picOne);
            if(!is_array($imgDimensions_picOne)){
                delete_uploaded_file($folder_path . $filename_picOne);
            }
            $imgDimensions_picTwo = @getimagesize($folder_path . $filename_picTwo);
            if(!is_array($imgDimensions_picTwo)){
                delete_uploaded_file($folder_path . $filename_picTwo);
            }
            // Höhe & Breite
            $width_picOne = $imgDimensions_picOne[0];
            $height_picOne = $imgDimensions_picOne[1];
            $width_picTwo = $imgDimensions_picTwo[0];
            $height_picTwo = $imgDimensions_picTwo[1];

            // Überprüfung der Bildgröße
            if (!empty($pairview_icondims)) {
                list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($pairview_icondims));
                if($width_picOne != $maxwidth || $height_picOne != $maxheight){		
                    $pairview_error[] = $lang->sprintf($lang->pairview_error_picOne_upload_icondims, $pairview_icondims);
                }
                if($width_picTwo < $maxwidth || $height_picTwo < $maxheight){		
                    $pairview_error[] = $lang->sprintf($lang->pairview_error_picTwo_upload_icondims, $pairview_icondims);
                }
            } else {
                // ob das Bild quadratisch sein muss
                if ($pairview_iconsquare == 1) {
                    if($width_picOne / $height_picOne != 1) {
                        $pairview_error[] = $lang->pairview_error_picOne_upload_iconsquare;
                    }
                    if($width_picTwo / $height_picTwo != 1) {
                        $pairview_error[] = $lang->pairview_error_picTwo_upload_iconsquare;
                    }
                }
            }

            // Überprüfung der Dateigröße
            $max_size = $pairview_iconsize*1024; 
            if($_FILES['pic_one']['size'] > $max_size) {
                $pairview_error[] = $lang->sprintf($lang->pairview_error_picOne_upload_size, get_friendly_size($max_size));
            }
            if($_FILES['pic_two']['size'] > $max_size) {
                $pairview_error[] = $lang->sprintf($lang->pairview_error_picTwo_upload_size, get_friendly_size($max_size));
            }

            // Überprüfung der Dateiendung
            if(!in_array($imageFileType_picOne, $allowed_extensions) AND !empty($_FILES['pic_one']['name'])) {
                $pairview_error[] = $lang->sprintf($lang->pairview_error_picOne_upload_file, $imageFileType_picOne);
            }
            if(!in_array($imageFileType_picTwo, $allowed_extensions) AND !empty($_FILES['pic_two']['name'])) {
                $pairview_error[] = $lang->sprintf($lang->pairview_error_picTwo_upload_file, $imageFileType_picTwo);
            }

            // Pic 1 nicht ausgefüllt
            if(empty($_FILES['pic_one']['name'])) {
                $pairview_error[] = $lang->pairview_error_picOne_upload;    
            } else {
                $pic_one = $db->escape_string($filename_picOne);
            }
            // Pic 2 nicht ausgefüllt
            if(empty($_FILES['pic_two']['name'])) {
                $pairview_error[] = $lang->pairview_error_picTwo_upload;  
            } else {
                $pic_two =  $db->escape_string($filename_picTwo);
            }
   
        }
        // Externe Links
        else {
            // SSL VERSCHLÜSSELUNG
            $ssl_check = 'https://';

            // Pic 1 nicht ausgefüllt
            if(empty($mybb->get_input('pic_one'))) {
                $pairview_error[] = $lang->pairview_error_picOne_extern;    
            } else {
                $pos_One = strpos($mybb->get_input('pic_one'), $ssl_check);
                if ($pos_One !== false) {
                    $pic_one = $db->escape_string($mybb->get_input('pic_one'));
                } else {
                    $pairview_error[] = $lang->pairview_error_picOne_extern_ssl; 
                }
            }
            // Pic 2 nicht ausgefüllt
            if(empty($mybb->get_input('pic_two'))) {
                $pairview_error[] = $lang->pairview_error_picTwo_extern;  
            } else {
                $pos_Two = strpos($mybb->get_input('pic_two'), $ssl_check);
                if ($pos_Two !== false) {
                    $pic_two = $db->escape_string($mybb->get_input('pic_two'));
                } else {
                    $pairview_error[] = $lang->pairview_error_picTwo_extern_ssl; 
                }
            }
        }

        // Error darf nicht ausgefüllt sein
        if(empty($pairview_error)) {
        
            // Eintragen
            $new_record = array(
                "category" => $category,
                "partner_one" => (int)$mybb->user['uid'],
                "pic_one" => $pic_one,
                "partner_two" => (int)$partner_uid,
                "pic_two" => $pic_two  
            );

            // MyALERTS STUFF   
            if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('pairview_add');
                if ($alertType != NULL && $alertType->getEnabled()) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$partner_uid, $alertType);
                    $alert->setExtraDetails([
                        'username' => $mybb->user['username'],
                        'from' => $mybb->user['uid'],
                        'category' => $category,
                    ]);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }  
            }

            $db->insert_query("pairs", $new_record);
            redirect("misc.php?action=pairview", $lang->pairview_redirect_add);
        } else {
            $mybb->input['action'] = "pairview";
            $pairview_error = inline_error($pairview_error);

            if ($pairview_uploadsystem == 0) {
                delete_uploaded_file($folder_path . $filename_picOne);
                delete_uploaded_file($folder_path . $filename_picTwo);
            }
        }
   
    }

    // DIE ÜBERSICHT
    if($mybb->input['action'] == "pairview") {

        if(!isset($pairview_error)){
            $pairview_error = "";
        }

        // NAVIGATION
		if(!empty($listsnav_setting)){
            add_breadcrumb($lang->pairview_navigation_lists, $listsnav_setting);
            add_breadcrumb($lang->pairview_main, "misc.php?action=pairview");
		} else{
            add_breadcrumb($lang->pairview_main, "misc.php?action=pairview");
		}

        if(is_member($pairview_groups_setting)) {
    
            $category_select = "";
            foreach ($pairview_category as $category) {
                $category_select .= "<option value='{$category}'>{$category}</option>";
            }

            $own_character = $db->fetch_field($db->simple_select("users", "username", "uid = '{$user_id}'"), "username");

            if ($pairview_uploadsystem == 0) {
                require_once MYBB_ROOT."inc/functions.php";
                $iconsize = $lang->sprintf($lang->pairview_add_iconsize, get_friendly_size($pairview_iconsize*1024));

                if (!empty($pairview_icondims)) {
                    $icondims = $lang->sprintf($lang->pairview_add_icondims, $pairview_icondims);
                } else {
                    if ($pairview_iconsquare == 1) {
                        $icondims = $lang->pairview_add_iconsquare;
                    } else {
                        $icondims = "";
                    }
                }

                eval("\$pairview_add = \"".$templates->get("pairview_add_upload")."\";");
            } else {
                eval("\$pairview_add = \"".$templates->get("pairview_add_link")."\";");
            }
        }

        foreach ($pairview_category as $cat) {

            $query_pairs = $db->query("SELECT * FROM ".TABLE_PREFIX."pairs p
            WHERE p.category = '$cat'
            ORDER BY (SELECT username FROM ".TABLE_PREFIX."users WHERE uid = p.partner_one) ASC
            ");

            $pair_bit = "";
            $pair_options = "";
            while($pair = $db->fetch_array($query_pairs)) {

                // LEER LAUFEN LASSEN
                $pid = "";
                $category = "";
                $partner_one = "";
                $pic_one = "";
                $partner_two = "";
                $pic_two = "";

                $pid = $pair['pid'];
                $category = $pair['category'];

                // Icons für Gäste verstecken
                if ($pairview_icon_guest_setting = 1) {

                    if ($user_id == 0) {
                        $pic_one = $theme['imgdir']."/".$pairview_defaulticon_setting;
                        $pic_two = $theme['imgdir']."/".$pairview_defaulticon_setting;
                    } else {
                        if ($pairview_uploadsystem == 0) {
                            $folder_path =  "uploads/pairview";
                            $pic_one = $folder_path."/".$pair['pic_one'];
                            $pic_two = $folder_path."/".$pair['pic_two'];
                        } else {
                            $pic_one = $pair['pic_one'];
                            $pic_two = $pair['pic_two'];
                        }
                    }
                } else {
                    if ($pairview_uploadsystem == 0) {
                        $folder_path =  "uploads/pairview";
                        $pic_one = $folder_path."/".$pair['pic_one'];
                        $pic_two = $folder_path."/".$pair['pic_two'];
                    } else {
                        $pic_one = $pair['pic_one'];
                        $pic_two = $pair['pic_two'];
                    }
                }

                // Usernamen bilden - Partner 1
                $username_one = $db->fetch_field($db->simple_select("users", "username", "uid = '".$pair['partner_one']."'"), "username");
                $partner_one = build_profile_link($username_one, $pair['partner_one']);

                // Username bilden - Partner 2
                $username_two = $db->fetch_field($db->simple_select("users", "username", "uid = '".$pair['partner_two']."'"), "username");
                $partner_two = build_profile_link($username_two, $pair['partner_two']);

                // Optionen
                $pair_options = "";
                if($user_id == $pair['partner_one'] OR $user_id == $pair['partner_two']) {
                    $pair_options = "<div class=\"pairbit_options\"><a href=\"misc.php?action=pairview_edit&amp;pairID=".$pid."\">".$lang->pairview_options_edit."</a> <a href=\"misc.php?action=pairview&pairview_delete=".$pid."\">".$lang->pairview_options_delete."</a></div>";
                } else {
                    $pair_options = "";
                }

                eval("\$pair_bit .= \"".$templates->get("pairview_pair")."\";");
            }

            eval("\$pairview_categories .= \"" . $templates->get ("pairview_category") . "\";");
        }

        // PÄRCHEN LÖSCHEN
        $delete = $mybb->get_input('pairview_delete');
        if($delete) {

            // MyALERTS STUFF   
            if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {

                $partner_one = $db->fetch_field($db->simple_select("pairs", "partner_one", "pid = '{$delete}'"), "partner_one");

                // Überprüfen, welcher Partner man selbst ist
                if ($mybb->user['uid'] == $partner_one) {
                    $partner_uid = $db->fetch_field($db->simple_select("pairs", "partner_two", "pid = '{$delete}'"), "partner_two");
                } else {
                    $partner_uid = $partner_one;
                }

                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('pairview_delete');
                if ($alertType != NULL && $alertType->getEnabled()) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$partner_uid, $alertType);
                    $alert->setExtraDetails([
                        'username' => $mybb->user['username'],
                        'from' => $mybb->user['uid'],
                        'category' => $category,
                    ]);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }

            $db->delete_query("pairs", "pid = '$delete'");

            redirect("misc.php?action=pairview", $lang->pairview_redirect_delete);
        }

        // Listenmenü
        if($liststype_setting != 2){
            // Jules Plugin
            if ($liststype_setting == 1) {
                $query_lists = $db->simple_select("lists", "*");
                while($list = $db->fetch_array($query_lists)) {
                    eval("\$menu_bit .= \"".$templates->get("lists_menu_bit")."\";");
                }
                eval("\$lists_menu = \"".$templates->get("lists_menu")."\";");
            } else {
                eval("\$lists_menu = \"".$templates->get($listsmenu_setting)."\";");
            }
        }

        eval("\$page = \"".$templates->get("pairview")."\";");
        output_page($page);
        die();
    }

    // BEARBEITUNG SPEICHERN
    if($mybb->input['action'] == "pairview_edit_do"){

        $pairview_edit_error = array();

        $pairID = $mybb->get_input('pairID');

        // Neue Kategorie
        $category_new = $db->escape_string($mybb->get_input('category'));
        // Alte Kategorie
        $category_old = $db->fetch_field($db->simple_select("pairs", "category", "pid = '".$pairID."'"), "category");

        // Partner-UIDS
        $uid_one = $db->fetch_field($db->simple_select("pairs", "partner_one", "pid = '".$pairID."'"), "partner_one");
        $uid_two = $db->fetch_field($db->simple_select("pairs", "partner_two", "pid = '".$pairID."'"), "partner_two");

        if ($category_new !== $category_old) {

            // Zählen ob diese Variante schon eingetragen ist
            $this_variant = $db->query("SELECT * FROM ".TABLE_PREFIX."pairs 
            WHERE category = '".$category_new."'
            AND partner_one = '".$uid_one."'
            AND partner_two = '".$uid_two."'
            ");    
            $count_this = $db->num_rows($this_variant);
        
            // Zählen ob anders rum schon eingetragen ist
            $different_variant = $db->query("SELECT * FROM ".TABLE_PREFIX."pairs 
            WHERE category = '".$category_new."'
            AND partner_two = '".$uid_one."'
            AND partner_one = '".$uid_two."'
            ");
            $count_different = $db->num_rows($different_variant);
    
            if($count_this > '0' OR $count_different > '0'){
                $pairview_edit_error[] = $lang->pairview_error_double;    
            }

        }

        // ICONS
        // Upload Funktion
        if ($pairview_uploadsystem == 0) {

            require_once MYBB_ROOT."inc/functions_upload.php";
            require_once MYBB_ROOT."inc/functions.php";

            // Verzeichnis für die Icons
            $folder_path =  MYBB_ROOT."uploads/pairview/"; 

            // Neues Bild hochgeladen - Bild 1
            if(!empty($_FILES['pic_one']['name'])) {

                // Dateityp ermittel (.png, .jpg, .gif)
                $imageFileType_picOne = end((explode(".", $_FILES['pic_one']['name'])));
                
                // Bildname - Speichern
                $filename_picOne = 'pair'.$pairID.'_'.$uid_one.'_1.' . $imageFileType_picOne;

                // Hochladen
                move_uploaded_file($_FILES['pic_one']['tmp_name'], $folder_path . $filename_picOne);

                // Grafik-Größe
                $imgDimensions_picOne = @getimagesize($folder_path . $filename_picOne);
                if(!is_array($imgDimensions_picOne)){
                    delete_uploaded_file($folder_path . $filename_picOne);
                }
                // Höhe & Breite
                $width_picOne = $imgDimensions_picOne[0];
                $height_picOne = $imgDimensions_picOne[1];

                // Überprüfung der Bildgröße
                if (!empty($pairview_icondims)) {
                    list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($pairview_icondims));
                    if($width_picOne != $maxwidth || $height_picOne != $maxheight){		
                        $pairview_edit_error[] = $lang->sprintf($lang->pairview_error_picOne_upload_icondims_edit, $pairview_icondims);
                    }
                } else {
                    // ob das Bild quadratisch sein muss
                    if ($pairview_iconsquare == 1) {
                        if($width_picOne / $height_picOne != 1) {
                            $pairview_edit_error[] = $lang->pairview_error_picOne_upload_iconsquare_edit;
                        }
                    }
                }

                // Überprüfung der Dateigröße
                $max_size = $pairview_iconsize*1024; 
                if($_FILES['pic_one']['size'] > $max_size) {
                    $pairview_edit_error[] = $lang->sprintf($lang->pairview_error_picOne_upload_size_edit, get_friendly_size($max_size));
                }

                // Überprüfung der Dateiendung
                if(!in_array($imageFileType_picOne, $allowed_extensions) AND !empty($_FILES['pic_one']['name'])) {
                    $pairview_edit_error[] = $lang->sprintf($lang->pairview_error_picOne_upload_file_edit, $imageFileType_picOne);
                }

                // Korrekte Bezeichnung für die Bilder
                $filename_picOne_corr = str_replace("_1", "", $filename_picOne);
                $pic_one_new = $db->escape_string($filename_picOne_corr);

            } else {
                $pic_one_new = $db->fetch_field($db->simple_select("pairs", "pic_one", "pid = '".$pairID."'"), "pic_one");
            }

            if(!empty($_FILES['pic_two']['name'])) {

                // Dateityp ermittel (.png, .jpg, .gif)
                $imageFileType_picTwo = end((explode(".", $_FILES['pic_two']['name'])));
                
                // Bildname - Speichern
                $filename_picTwo = 'pair'.$pairID.'_'.$uid_two.'_1.' . $imageFileType_picTwo;

                // Hochladen
                move_uploaded_file($_FILES['pic_two']['tmp_name'], $folder_path . $filename_picTwo);

                // Grafik-Größe
                $imgDimensions_picTwo = @getimagesize($folder_path . $filename_picTwo);
                if(!is_array($imgDimensions_picTwo)){
                    delete_uploaded_file($folder_path . $filename_picTwo);
                }
                $width_picTwo = $imgDimensions_picTwo[0];
                $height_picTwo = $imgDimensions_picTwo[1];

                // Überprüfung der Bildgröße
                if (!empty($pairview_icondims)) {
                    list($maxwidth, $maxheight) = preg_split('/[|x]/', my_strtolower($pairview_icondims));
                    if($width_picTwo != $maxwidth || $height_picTwo != $maxheight){		
                        $pairview_edit_error[] = $lang->sprintf($lang->pairview_error_picTwo_upload_icondims_edit, $pairview_icondims);
                    }
                } else {
                    // ob das Bild quadratisch sein muss
                    if ($pairview_iconsquare == 1) {
                        if($width_picTwo / $height_picTwo != 1) {
                            $pairview_edit_error[] = $lang->pairview_error_picTwo_upload_iconsquare_edit;
                        }
                    }
                }

                // Überprüfung der Dateigröße
                $max_size = $pairview_iconsize*1024; 
                if($_FILES['pic_two']['size'] > $max_size) {
                    $pairview_edit_error[] = $lang->sprintf($lang->pairview_error_picTwo_upload_size_edit, get_friendly_size($max_size));
                }

                if(!in_array($imageFileType_picTwo, $allowed_extensions) AND !empty($_FILES['pic_two']['name'])) {
                    $pairview_edit_error[] = $lang->sprintf($lang->pairview_error_picTwo_upload_file_edit, $imageFileType_picTwo);
                }

                // Korrekte Bezeichnung für die Bilder
                $filename_picTwo_corr = str_replace("_1", "", $filename_picTwo);
                $pic_two_new =  $db->escape_string($filename_picTwo_corr);

            } else {     
                $pic_two_new = $db->fetch_field($db->simple_select("pairs", "pic_two", "pid = '".$pairID."'"), "pic_two");
            }
   
        }
        // Externe Links
        else {
            // SSL VERSCHLÜSSELUNG
            $ssl_check = 'https://';

            // Pic 1 nicht ausgefüllt
            if(empty($mybb->get_input('pic_one'))) {
                $pairview_edit_error[] = $lang->pairview_error_picOne_extern_edit;    
            } else {
                $pos_One = strpos($mybb->get_input('pic_one'), $ssl_check);
                if ($pos_One !== false) {
                    $pic_one_new = $db->escape_string($mybb->get_input('pic_one'));
                } else {
                    $pairview_edit_error[] = $lang->pairview_error_picOne_extern_ssl_edit; 
                }
            }
            // Pic 2 nicht ausgefüllt
            if(empty($mybb->get_input('pic_two'))) {
                $pairview_edit_error[] = $lang->pairview_error_picTwo_extern_edit;  
            } else {
                $pos_Two = strpos($mybb->get_input('pic_two'), $ssl_check);
                if ($pos_Two !== false) {
                    $pic_two_new = $db->escape_string($mybb->get_input('pic_two'));
                } else {
                    $pairview_edit_error[] = $lang->pairview_error_picTwo_extern_ssl_edit; 
                }
            }
        }

        // Error darf nicht ausgefüllt sein
        if(empty($pairview_edit_error)) {

            if ($pairview_uploadsystem == 0) {
                if(!empty($_FILES['pic_one']['name'])) {
                    // Korrekte Bezeichnung für die Bilder
                    $filename_picOne_corr = str_replace("_1", "", $filename_picOne);
                    // Neues Bild korrekt unbennen
                    rename($folder_path . $filename_picOne,$folder_path . $filename_picOne_corr);
                }

                if(!empty($_FILES['pic_two']['name'])) {
                    // Korrekte Bezeichnung für die Bilder
                    $filename_picTwo_corr = str_replace("_1", "", $filename_picTwo);
                    // Neues Bild korrekt unbennen
                    rename($folder_path . $filename_picTwo,$folder_path . $filename_picTwo_corr);
                }
            }
        
            // Eintragen
            $edit_record = array(
                "category" => $category_new,
                "pic_one" => $pic_one_new,
                "pic_two" => $pic_two_new
            );

            $partner_one = $db->fetch_field($db->simple_select("pairs", "partner_one", "pid = '".$pairID."'"), "partner_one");
            // Überprüfen, welcher Partner man selbst ist
            if ($mybb->user['uid'] == $partner_one) {
                $partner_uid = $db->fetch_field($db->simple_select("pairs", "partner_two", "pid = '".$pairID."'"), "partner_two");
            } else {
                $partner_uid = $partner_one;
            }

            // MyALERTS STUFF   
            if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {

                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('pairview_edit');
                if ($alertType != NULL && $alertType->getEnabled()) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$partner_uid, $alertType);
                    $alert->setExtraDetails([
                        'username' => $mybb->user['username'],
                        'from' => $mybb->user['uid'],
                        'category_new' => $category_new,
                        'category_old' => $category_old
                    ]);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }

            $db->update_query("pairs", $edit_record, "pid = '".$pairID."'");
            redirect("misc.php?action=pairview", $lang->pairview_redirect_edit);
        } else {
            $mybb->input['action'] = "pairview_edit";
            $pairview_edit_error = inline_error($pairview_edit_error);

            if ($pairview_uploadsystem == 0) {
                if(!empty($_FILES['pic_one']['name'])) {
                    delete_uploaded_file($folder_path . $filename_picOne);
                }
                if(!empty($_FILES['pic_two']['name'])) {
                    delete_uploaded_file($folder_path . $filename_picTwo);
                }
            }
        }

    }

    // PÄRCHEN BEARBEITEN
    if($mybb->input['action'] == "pairview_edit") {

        if(!isset($pairview_edit_error)){
            $pairview_edit_error = "";
        }

        $pairID = $mybb->get_input('pairID');
 
        // NAVIGATION
		if(!empty($listsnav_setting)){
            add_breadcrumb($lang->pairview_navigation_lists, $listsnav_setting);
            add_breadcrumb($lang->pairview_main, "misc.php?action=pairview");
            add_breadcrumb($lang->pairview_edit, "misc.php?action=pairview_edit");
		} else{
            add_breadcrumb($lang->pairview_main, "misc.php?action=pairview");
            add_breadcrumb($lang->pairview_edit, "misc.php?action=pairview_edit");
		}

        $edit_query = $db->query("SELECT * FROM ".TABLE_PREFIX."pairs p
        WHERE p.pid = '".$pairID."'
        ");
     
        $edit = $db->fetch_array($edit_query);

        // GÄSTE UND !PARTNER => ERROR
        if($mybb->user['uid'] == 0 OR ($user_id != $edit['partner_one'] AND $user_id != $edit['partner_two'])) {
            error_no_permission();
            return;
        } 

        // LEER LAUFEN LASSEN
        $pid = "";
        $category = "";
        $username_one = "";
        $pic_one = "";
        $username_two = "";
        $pic_two = "";

        // MIT INFOS FÜLLEN
        $pid = $edit['pid'];
        $category = $edit['category'];

        // Usernamen bilden - Partner 1
        $username_one = $db->fetch_field($db->simple_select("users", "username", "uid = '".$edit['partner_one']."'"), "username");

        // Username bilden - Partner 2
        $username_two = $db->fetch_field($db->simple_select("users", "username", "uid = '".$edit['partner_two']."'"), "username");

        // Icons
        if ($pairview_uploadsystem == 0) {
            $folder_path =  "uploads/pairview";
            $pic_one = $folder_path."/".$edit['pic_one'];
            $pic_two = $folder_path."/".$edit['pic_two'];
        } else {
            $pic_one = $edit['pic_one'];
            $pic_two = $edit['pic_two'];
        }
 
        // Kategorie Dropbox generien
        $category_select = "";
        foreach ($pairview_category as $cat) {
    
            // die bisherige Kategorie als ausgewählt anzeigen lassen
            if($category == $cat) {
                $checked_cat = "selected";
            } else {
                $checked_cat = "";
            }

            $category_select .= "<option value='".$cat."' ".$checked_cat.">".$cat."</option>";
        }

		// Listenmenü
		if($liststype_setting != 2){
            // Jules Plugin
            if ($liststype_setting == 1) {
                $query_lists = $db->simple_select("lists", "*");
                while($list = $db->fetch_array($query_lists)) {
                    eval("\$menu_bit .= \"".$templates->get("lists_menu_bit")."\";");
                }
                eval("\$lists_menu = \"".$templates->get("lists_menu")."\";");
            } else {
                eval("\$lists_menu = \"".$templates->get($listsmenu_setting)."\";");
            }
        }

		// TEMPLATE FÜR DIE SEITE
        if ($pairview_uploadsystem == 0) {
            require_once MYBB_ROOT."inc/functions.php";
            $iconsize = $lang->sprintf($lang->pairview_add_iconsize, get_friendly_size($pairview_iconsize*1024));
            if (!empty($pairview_icondims)) {
                $icondims = $lang->sprintf($lang->pairview_add_icondims, $pairview_icondims);
            } else {
                if ($pairview_iconsquare == 1) {
                    $icondims = $lang->pairview_add_iconsquare;
                } else {
                    $icondims = "";
                }
            }

            eval("\$page = \"".$templates->get("pairview_edit_upload")."\";");
        } else {
            eval("\$page = \"".$templates->get("pairview_edit_link")."\";");
        }
		output_page($page);
		die();
    }

}

// ONLINE ANZEIGE - WER IST WO
function pairview_online_activity($user_activity) {

    global $parameters, $user;

    $split_loc = explode(".php", $user_activity['location']);
    if($split_loc[0] == $user['location']) {
        $filename = '';
    } else {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    
    switch ($filename) {
        case 'misc':
        if($parameters['action'] == "pairview") {
            $user_activity['activity'] = "pairview";
        }
        break;
    }
      

    return $user_activity;
}

function pairview_online_location($plugin_array) {

    global $mybb, $theme, $lang;

	if($plugin_array['user_activity']['activity'] == "pairview") {
		$plugin_array['location_name'] = "Sieht sich die <a href=\"misc.php?action=pairview\">Pärchenübersicht</a> an.";
	}


    return $plugin_array;
}

$plugins->add_hook("admin_user_users_delete_commit_end", "pairview_user_delete");
// GELÖSCHTEN USER AUS DER DB ENTFERNEN
function pairview_user_delete(){

   global $db, $cache, $mybb, $user;

   // EINSTELLUNGEN
   $uploadsystem = $mybb->settings['pairview_uploadsystem'];

   // UID gelöschter Chara
   $deleteChara = (int)$user['uid'];

   // Bilder löschen
   if ($uploadsystem == 0) {

    require_once MYBB_ROOT."inc/functions_upload.php";
    require_once MYBB_ROOT."inc/functions.php";

    // Verzeichnis für die Icons
    $folder_path =  MYBB_ROOT."uploads/pairview/"; 

    $allpairs_query = $db->query("SELECT * FROM ".TABLE_PREFIX."pairs p
    WHERE p.partner_one = '".$deleteChara."' OR p.partner_two = '".$deleteChara."'
    ");

    $picOne_names = [];
    $picTwo_names = [];
    while($allpairs = $db->fetch_array($allpairs_query)) {
        $picOne_names[] = $allpairs['pic_one'];
        $picTwo_names[] = $allpairs['pic_two'];
    }
    $all_picNames = array_merge($picOne_names, $picTwo_names);
               
    foreach ($all_picNames as $filename) {
        delete_uploaded_file($folder_path . $filename);
    }
   }
   
   $db->delete_query('pairs', "partner_one = '".$deleteChara."'");
   $db->delete_query('pairs', "partner_two = '".$deleteChara."'");

}

// MyALERTS
function pairview_myalerts() {

    global $mybb, $lang;
	$lang->load('pairview');

    // HINZUFÜGEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_pairviewAddFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
                $this->lang->myalerts_pairview_add, 
                $alertContent['username'],
                $alertContent['from'],
                $alertContent['category']               
            );  
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->pairview) {
	            $this->lang->load('pairview');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/misc.php?action=pairview';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_pairviewAddFormatter($mybb, $lang, 'pairview_add')
		);
    }

    // LÖSCHEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_pairviewDeleteFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
                $this->lang->myalerts_pairview_delete, 
                $alertContent['username'],
                $alertContent['from'],
                $alertContent['category']               
            );  
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->pairview) {
	            $this->lang->load('pairview');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/misc.php?action=pairview';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_pairviewDeleteFormatter($mybb, $lang, 'pairview_delete')
		);
    }

    // BEARBEITEN - ICONS
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_pairviewEditFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
                $this->lang->myalerts_pairview_edit, 
                $alertContent['username'],
                $alertContent['from']              
            );  
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->pairview) {
	            $this->lang->load('pairview');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/misc.php?action=pairview';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_pairviewEditFormatter($mybb, $lang, 'pairview_edit')
		);
    }

}

function pairview_getNextId($tablename){
    global $db;
    $databasename = $db->fetch_field($db->write_query("SELECT DATABASE()"), "DATABASE()");
    $lastId = $db->fetch_field($db->write_query("SELECT AUTO_INCREMENT FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = '" . $databasename . "' AND TABLE_NAME = '" . TABLE_PREFIX . $tablename . "'"), "AUTO_INCREMENT");
    return $lastId;
}
