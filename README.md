# DocMap Reviews Plugin

About
-----
This plugin enables the displaying of preprint reviews via [DocMaps](https://docmaps.knowledgefutures.org).

In order to do this the plugin utilises the [Sciety DocMaps API v1](https://sciety.org/docmaps/v1/).

Features
-----
* Display reviews on the preprint public page
* As new reviews are added to the related DocMap, the preprint public page will automatically update and display them
 upon the next page load
* Users can choose to opt out of displaying reviews on their preprint public page prior to publication should they wish


License
-------
This plugin is licensed under the GNU General Public License v3. See the file LICENSE for the complete terms of this license.

System Requirements
-------------------
OPS 3.4.0

Install
-------

To install via the OPS Admin UI:

* Go to Settings -> Website -> Plugins -> Upload A New Plugin -> Then select the tar/zip file for the plugin and Save.


To install manually without the OPS Admin UI:

* Copy the release source or unpack the release package into the OPS plugins/generic/docMapReviews/ folder.
* Run `php tools/upgrade.php upgrade` from the OPS folder. This creates the needed database tables.


Enable
-------

* Go to Settings -> Website -> Plugins -> Generic Plugin -> DocMap Reviews Plugin and enable the plugin.