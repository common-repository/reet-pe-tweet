=== Reet-pe-Tweet ===
Contributors: maltpress
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QBJVPRQJ7V9RE
Tags: twitter, widget, simple
Requires at least: 3.0.0
Tested up to: 3.1.2
Stable tag: trunk

Simple Twitter widget for sidebars. Show/hide @replies & "via" links. #tags link to Twitter search, @names link to profiles.

== Description ==

This is a simple Twitter widget for your sidebar. I know there are several out there, but I needed something very specific for my site and decided to make it a bit more general and release it into the world...

This plugin uses basic caching so you don't hit the API rate limit (it's using the user.timeline API method rather than the deprecated RSS method). It will need to write a (small) xml file to your uploads folder, and to update this regularly, so make sure the directory is writeable. The cache time can be changed for busy sites or quiet sites where you want more immediate effect - but the default updates every minute (if required, it won't be active if no-one's on your site) so you should be fine.

User controls: 

*   Title: widget title
*   Twitter user name: feed to show (must be public)
*   Cache time: put this up for really busy sites, but you probably won't need to
*   Show replies: show tweets which are @replies (won't remove tweets which *mention* someone)
*   Show "via" link: shows your tweet method (i.e. "Via Tweetdeck") with link
*   Tweets to show: number of tweets, up to 20 (with @replies on - see FAQ)

== Installation ==

1. Upload the reet-pe-tweet directory to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place your widget. Make sure you set a Twitter user name.
4. If you get errors on activating/first loading your page, make sure your wp-content/uploads folder is there and writeable.

== Frequently Asked Questions ==

= How do I style the widget? =

The output of the widget is this:

`<h3 class="widget-title">Latest tweets</h3>
<ul class="reet-pe-tweet-ul">
<li class="tweet-single">
Tweet content
<br>
<span class="tweet-date">Thursday 28 April</span>
<br>
<span class="tweet-source">Via <a href="#">Tweetdeck</a></span>
</li>
</ul>`

Add the appropriate styles to your CSS and you should be good to go.

= Can I change the date format? =

Not yet, sorry. Next version.

= Fewer tweets than I expected are showing. What's with that? =

If you @reply a lot but turn replies off on this widget, you might see this. Basically, the plugin pulls the last 20 tweets, caches them, then removes @replies if you turn them off. So if you've got 15 @replies in your stream, then you've only really got 5 tweets to show. Sorry. I made a decision to keep things simple and resource-friendly by using only a basic API call. If you know what you're doing, you can increase the count by changing the code (lines 255 and 284 have the API calls on them).

= Do you have a plan for improvements and more functionality? =

Aside from adding some control over the date format (because in most cases it would be good to see time), and getting it to do a clean uninstall (removing the old cache file when you disable it), no. This is a simple, almost throwaway plugin. There are other Twitter widgets out there which do more, or you can adapt this one as you see fit. If you have specific requirements, there are lots of developers (me included) who'd be happy to write what you need for money, or beer, or hugs or something. Depending on the developer. Sorry - I'm not really a believer in adding hundreds of complex settings to plugins.

= Stupid name =

Well, I like it. As I do with plugins, I asked Twitter what it should be called; this time the winner was @silv3r who has no idea what it is he's actually named because he doesn't use Wordpress. I think he's a fan of [Jackie Wilson](http://www.youtube.com/watch?v=xJ3-NnNx6Zs "Jackie Wilson's Reet Petite on YouTube")

== Screenshots ==

1. The Widget control panel
2. Widget in place and unstyled on the 2010 theme, with replies off and "via" links on.

== Changelog ==

= 0.2.1 =
* Fixed date issue where Tweet dates fell back to Jan 1 on newer PHP versions

= 0.2 =
* Fixed error whereby cache would be emptied and error shown if connection to Twitter lost
* Fixed error with dates and times on some versions of PHP

= 0.1 =
* Plugin released