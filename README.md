*Current Version:1.7.5*

**Versions lower than 1.7.4 contain a sql injection vulnerability. If you are using a version older than this, please update immediately**

# Marcato Festival's XML WordPress Importer

NOTE: _This tool is provided open source, as is, and free of charge. Its purpose is to give Wordpress web developers a head start importing data from Marcato Festival XML Feeds into a website's Wordpress database. It is recommended for developers who are familiar with Wordpress, PHP and CSS. We welcome code contributions as pull requests._

**The Marcato Festival team does not provide technical support for this plugin.**

&nbsp;
## Installation and Setup

1. Place these files into your WordPress plugins folder
2. Go to the Plugins section in WordPress and activate Marcato XML Importer. You should now see menu options with the Marcato logo and Artists/Venues/Shows/Workshops
3. Under the settings menu there should now be a Marcato option.
4. Enter your Marcato Organization ID in the field and click "Save Changes".

Clicking Import Now will download and parse marcato's xml feeds creating posts representing your marcato data.

All NEW records added this way will be set as Pending, and you will need to publish them from within Wordpress to make them appear on the site.

To get to the public side of the marcato data you would go to http://yourwebsite.com/SLUG
The slug is set as the post type. So for Marcato Artists the slug is artists.
If permalinks are disabled or that doesn't work properly http://yourwebsite.com/?post_type=marcato_TYPE should link to the post type.
With type being artist, venue, show, or workshop.


## Features

### Featured Images

Enabling the "attach photos as featured image" option will cause the plugin to download photos from the Marcato server and save them as the featured images of the posts created in your Wordpress database.
This is useful if your Wordpress theme makes use of Post Thumbnails/Featured Image.

### Auto Embed Links

Enabling the "embed links" option will cause the plugin to automatically embed any embedable YouTube, Vimeo, or Soundcloud links coming from the websites on artists in your Marcato account.

### Excerpts

Enabling excerpts will set the Artist's short bio as artist post excerpts and Show/Workshop web descriptions as show/workshop post excerpts.

### Include Custom Fields

Including XML data as custom fields may be useful in conjunction with other plugins or themes.

### Auto Update

Enabling auto updating will set up a wordpress job that will perform an import every hour.

## Customization

There is a marcato.css file in the plugin's css folder that contains all the styles for marcato-specific content.

You can also create template files for the various post types if you would like to customize them further.
See http://codex.wordpress.org/Post_Types#Template_Files
Marcato's post types are name-spaced with marcato_ so our post types are marcato_artist, marcato_venue, marcato_show, marcato_workshop


## Common issues

1. If you get a message "error loading xml file" this likely means that cURL is not enabled on your system. Check the output of phpinfo(); to determine if cURL is disabled and enable it, or contact your system administrator.
This can also happen if that particular feed is not turned on in your marcato account, or it is empty.

2. If you change certain WordPress settings (such as your permalink structure) you may need to update your Marcato data by clicking Import Now on the Marcato settings page.
