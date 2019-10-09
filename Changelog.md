**1.7.5**
* Update the marcato_xml_url variable to use HTTPS and point to the WWW subdomain, as that will make use of Cloudflare's CDN and improve caching.

**1.7.4**
* A sql injection vulnerability was discovered (thanks dark-panda). It was contained in code that was no longer necessary for the plugin to function and was removed.

**1.7.3**
* When the "Set the show and workshop times listed in the artist post to their performance times instead, if available?" option was checked, artist performance times were being effected by the php timezone, and showing as incorrect if it was not UTC. Now using different xml fields to avoid this issue.

**1.7.2**
* Now pulling mp3_attachment fields on artists into custom fields on the artist posts

**1.7.1**
* Changed how the auto-update wordpress cron job gets set up, to make it more reliable

**1.7.0**
* Now supports pulling data from multiple organizations
* Now have the option to use the xml labels set for custom fields in marcato as part of the meta data labels in wordpress, instead of the regular label
* Now including website information from venue's in the post meta data

**1.6.0**
* Vendor post bodies will now include websites

**1.5.0**
* Suppress some confusing xml errors
* Fix image shortcode parsing to avoid php warnings
* Vendor posts will now pull in web/press photos from xml feed like artists do already

**Version 1.4.0**
* Added an additional meta data item for websites pulled in from xml feeds in the style of marcato\_artist\_website\_Website Label\_url as the marcato\_artist\_website\_index\_url + marcato\_artist\_website\_index\_label was not easy to use.
* Now using MAJOR.MINOR.PATCH versioning

**Version 1.3.15**
* Vendors now include custom field data as meta data on posts when enabled

**Version 1.3.14**
* Fix compatibility issue with PHP versions < 5.5

**Version 1.3.13**
* Fixed bug causing an error message while importing if a feed existed but was empty
* Fixed ereg_replace deprecation warning

**Version 1.3.12**
* Workshop schedule table will now link to imported contacts

**Version 1.3.11**
* Check to see if there is a performance/presentation map before attempting to use them as a map to avoid some php warnings
* Updated copy in unable to load xml file error message as it is now possible to turn feeds on and off within marcato
* Check if custom fields array isn't there before trying to use it
* Suppressing error messages when attempting to load xml files from marcato to avoid confusion.

**Version 1.3.10**
* Fixed a bug in artist parsing with "include photo in post body" selected but not the featured image option which resulted in the image not working

**Version 1.3.9**
* Custom fields from the xml feeds will now be added as meta data to posts

**Version 1.3.8**
* Contact profile photos will now be attached to posts as featured images if the featured image option is enabled

**Version 1.3.7**
* Use original image uploaded as show poster instead of a smaller resized version

**Version 1.3.6**
* Fixed bug in links within artist schedules

**Version 1.3.5**
* Version numbers not consistent throughout plugin.

**Version 1.3.4**
* Shows excerpt is now pulling from the correct xml value.

**Version 1.3.3**
* Add venue directions to post_meta (from tflight)
* Can now import contacts and vendors (tflight)
* Added additional post meta fields and fixed longitude typo in venue meta data. (tflight)
* Show and workshop meta data on artists is now ordered chronologically

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
