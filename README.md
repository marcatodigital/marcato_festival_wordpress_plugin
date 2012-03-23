~Current Version:1.0.7~

# Marcato Festival's XML WordPress Importer

Place these files into your WordPress plugins folder.
Go to the Plugins section in WordPress and activate Marcato XML Importer.
Under the settings menu there should now be a Marcato option.
Enter your Marcato Organization ID in the field and click "Save Changes".

When you click to save the changes the plugin will fetch our XML feeds and parse them and save the data to your Wordpress database.
It also sets up a Wordpress job that will redo this automatically every hour, however it can be done manually whenever you like by clicking "Import now"
(The Marcato XML feeds are updated every few minutes)

Enabling the "attach photos as featured image" option will cause the plugin to download photos from the Marcato server and save them as the featured image of the posts created in your Wordpress database.
This is useful if your Wordpress theme makes use of Post Thumbnails/Featured Image.
Note that enabling this option will result in the image no longer being directly embedded in the post body.

Enabling the "embed video links" option will cause the plugin to automatically embed any YouTube or Vimeo videos that are linked to from the website fields on artists in your Marcato account.

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