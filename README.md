~Current Version:1.3.3~

# Marcato Festival's XML WordPress Importer (BETA)

**NOTE: _This tool is a beta project and provided open source, as is, and free of charge. Its purpose is to give Wordpress web developers a head start importing data from Marcato Festival XML Feeds into a website's Wordpress database. It is recommended for developers who are familiar with Wordpress, PHP and CSS. We welcome code contributions as pull requests. Feature requests, feedback and recommendations can be submitted to support@marcatodigital.com. The Marcato Festival team does not provide technical support for this plugin._** 

Place these files into your WordPress plugins folder.
Go to the Plugins section in WordPress and activate Marcato XML Importer.
Under the settings menu there should now be a Marcato option.
Enter your Marcato Organization ID in the field and click "Save Changes".

If you get a message "error loading xml file" this likely means that cURL is not enabled on your system. Check the output of phpinfo(); to determine if cURL is disabled and enable it, or contact your system administrator.

When you click to save the changes the plugin will fetch our XML feeds and parse them and save the data to your Wordpress database.
It also sets up a Wordpress job that will redo this automatically every hour, however it can be done manually whenever you like by clicking "Import now"
(The Marcato XML feeds are updated every few minutes)

Enabling the "attach photos as featured image" option will cause the plugin to download photos from the Marcato server and save them as the featured image of the posts created in your Wordpress database.
This is useful if your Wordpress theme makes use of Post Thumbnails/Featured Image.

Enabling the "embed video links" option will cause the plugin to automatically embed any YouTube or Vimeo videos that are linked to from the website fields on artists in your Marcato account.

Enabling excerpts will set the Artist's short bio as artist post excerpts and Show/Workshop web descriptions as show/workshop post excerpts.

Including XML data as custom fields may be useful in conjunction with other plugins or themes.

If you change certain WordPress settings (such as your permalink structure) you may need to update your Marcato data by clicking Import Now on the Marcato settings page.

You should now see menu options with the Marcato logo and Artists/Venues/Shows/Workshops that should be populated with your data.
All NEW records added this way will be set as Pending, and you will need to publish them from within Wordpress to make them appear on the site.

As for styling, it will use whatever styles you already have for most of the base stuff.
There is a marcato.css file in the plugin's css folder that contains all the styles that can currently be used for marcato-specific content.

You can also create template files for the various post types if you would like to customize them further.
See http://codex.wordpress.org/Post_Types#Template_Files
Marcato's post types are name-spaced with marcato_ so our post types are marcato_artist, marcato_venue, marcato_show, marcato_workshop

To get to the public side of the marcato data you would go to http://yourwebsite.com/SLUG
The slug is set as the post type. So for Marcato Artists the slug is artists.
If permalinks are disabled or that doesn't work properly http://yourwebsite.com/?post_type=marcato_TYPE should link to the post type.
With type being artist, venue, show, or workshop.
