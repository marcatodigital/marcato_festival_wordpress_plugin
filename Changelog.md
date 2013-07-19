**Version 1.3.3**  
* Added directions and description Custom Fields (post_meta) to Venues posts
* bumped version number in plugin, readme, and changelog

**Version 1.3.2**  
* Fixed venue link on show and workshop pages

**Version 1.3.1**  
* Updated Youtube URL detection  
* Imported websites with https:// instead of http:// will now work

**Version 1.3.0**  
* Added an option to select the image size of the photo included in the post body when using that option.

**Version 1.2.9**  
* Added an option to include individual timeslot times in artist profiles instead of the show/performance times.  
* Posts in wordpress will now be removed if their data is no longer present in the xml feed when importing (they were deleted in marcato or are no longer public).

**Version 1.2.8**  
* The Updater should no longer check on every page load for an update to the plugin.

**Version 1.2.7**  
* Loading of xml files now handled by curl if allow\_url\_fopen is disabled. More detailed error message when xml feeds are unable to be loaded.

**Version 1.2.6**  
* Changed usort to not use an anonymous function which is only available in PHP 5.3.0+

**Version 1.2.5**
* Using a larger version of images to import as featured images to give user more flexibility with image resizing

**Version 1.2.4**  
* When importing featured images the plugin will now check both the fingerprint AND the existence of a featured image to determine whether a new one should be downloaded.

**Version 1.2.3**  
* Add option to include artist schedule at the bottom of artist posts.  
* Fixed the show and workshop public check  

**Version 1.2.2**  
* Don't include shows and workshops in artist and venue posts that are not set to public

** Version 1.2.1**  
* Updated the start time fields to use the new start time unix fields

**Version 1.2.0**  
* Fixed bug where the set organization ID was not being properly remembered

**Version 1.1.9**  
* Improved marcato-link shortcode with the ability to specify a meta-data field to get the required id  
* Added show and workshop metadata info to venue posts

**Version 1.1.8**  
* Added an option to disable the auto-update of marcato data

**Version 1.1.7**  
* Added event contact fields to workshop posts  
* Added a marcato-field shortcode that accepts field and label attributes that currently works for displaying specific artist websites

**Version 1.1.6**  
* Fixed bug in artist photos

**Version 1.1.5**  
* Updating of post meta data works now  
* Added a marcato-link shortcode that gets used to link between marcato posts. This should avoid the odd bugs resulting from the strange way the linking was currently being done.

**Version 1.1.4**  
* Use the web-versions of images instead of the originally uploaded images to avoid memory issues with large filesizes  
* Sanitize the string that will be used as the image filename

**Version 1.1.3**  
* Added seating to the show and workshop posts meta data  
* Added Artist Genres as a custom Taxonomy - From gavinsmith

**Version 1.1.2**  
* Added an option to include images in post bodies that is independent of the featured image option.  
* Updated Github Updater for Update warnings. Now checks for updates every 6 hours

**Version 1.1.1**  
* Added IDs to artist shows and workshops metadata  
* Added missing Street to venue addresses  
* Marcato CSS file now included in editor

**Version 1.1.0**  
* Added Excerpts to the Marcato post types which pull in information from artist short bios and show/workshop web descriptions  
* Added the option to include xml fields as meta data. Useful if you want to make your own template files for the post or using another plugin that uses meta data

**Version 1.0.10**  
* Venue Photos will now be set as featured image if the option is selected

**Version 1.0.9**  
* Only download featured images if the image has actually changed using new fingerprint field from Marcato xml feeds

**Version 1.0.8**  
* Fixed a bug where embed codes were being wiped when the scheduled job updated posts. Apparently only admins can use HTML to embed things. Now using [embed] shortcode  
* Soundcloud tracks are now embedded

**Version 1.0.7**  
* Marcato's Post types will now be pulled into category/tag pages that they are categorized/tagged with.  
* Artist posts will now use the artist's press photo if a web photo is not available.  
* Artist images that are embedded within post bodies now use a more web-optimized version instead of the original one that was uploaded. (Only if you aren't using the featured-image option).  
* Added genre to artist posts.

**Version 1.0.6**  
* Artist websites now add the name of the website as a css class to the link.

**Version 1.0.5**  
* Artist websites now have http:// prepended to the urls if it is missing.

**Version 1.0.4**  
* Fixed bug where the featured image variable was not being unset if the artist did not have a featured image so it wound up using the image from the previous artist.

**Version 1.0.3**  
* Fixed bug in embedding video links where the variable wasn't being cleared between artists and each successive artist was then including all the previous embed codes.  
* Fixed bug in featured image downloading for users using an older version of PHP.

**Version 1.0.2**  
* Added updater from https://github.com/jkudish/WordPress-GitHub-Plugin-Updater so users can get out-of-date notifications for the plugin.

**Version 1.0.1**  
* Fixed the Artist parser so that all of the websites are being inserted into the post.  
* Added an option to include Artist photos, Show/Workshop posters as their post's featured image.  
* Added an option to automatically embed any YouTube or Vimeo videos that websites imported from Marcato link to.
