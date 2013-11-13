=== Bleep Filter ===
Contributors: Nathan Lampe
Tags: profanity filter, word filter, content filter, phonetic filter, bleep filter
Requires at least: 3.5.1
Tested up to: 3.7.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An advanced word and content filter perfect for passively eliminating profanity and spoilers.

== Description ==

The Bleep Filter plugin is a free and open source advanced content filtering plugin for WordPress. Commonly used as a bad word filter, this plugin offers a variety of applications for your needs. Easily add the words you want to filter out and the plugin will find those words in your blog's comments, posts, and rss feeds and passively replace them in a variety of styles.

Using a highly advanced phonetic algorithm, not only is the spelling being detected but also how the word sounds. This makes it much more difficult for mischievous posters to bypass the filter intentionally.

With the Bleep Filter plugin all you have to do is add your words and the plugin takes care of the rest.

== Installation ==

1. Upload `bleep-filter` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently asked questions ==
= Will my posts and comments be edited permanently? =
No. Bleep Filter works passively ensuring that your original content, posts, and comments remain the same even if you delete or deactivate the plugin.

= How do I prevent certain words from being filtered? =

If the filter is picking up words that it shouldn't you can add exception words or phrases using the plugin's exceptions words list to prevent them from being filtered.

= How does it work? =
Most bad word filters use simple one for one matching of words. This makes filtering difficult because it forces you to think of every possible spelling for each word in every situation for it to be effective.

For example you want to filter the word shazbot. Normally you add the word to the filter and hope the correct spelling is used. What happens if the user types the word shaazzzboughtte! or even sh@zb0t!? Would the filter pick it up? Bleep Filter would.

== Screenshots ==

1. Bleep Filter Settings Page
2. A spoiler word on a post

== Changelog ==
= 1.0 =
* improved word matching and performance
* added replacement words
* removed intensity settings

= 0.4 =
* added csv importing
* updated settings page link
* removed filtering while logged in as admin

= 0.3 =
* now includes titles and excerpts

= 0.2 =
* initial release

== Upgrade notice ==

= 1.0 =
This version now includes improved matching and performance. Can now replace bad words with a replacement word.
