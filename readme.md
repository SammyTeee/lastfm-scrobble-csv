## Last.FM, you're bringing me down.

* Open Last.FM desktop client
* Help
* Diagnostics
* Scrobble iPod
  * _ITUNES NOT RUNNING!_
* OH FFS.

Since they're a proven non-revenue generating tech company, Last.FM is now seemingly working with a skeleton support staff and dev team. That means their wonky desktop application used to scrobble your iPhone is unlikely to see further updates. No, I will not listen to music via your native app.

Since I got tired of it randomly deciding my iPhone-to-iTunes sync should be ignored, I chucked together this script so I can quickly copy and paste the most recent plays from iTunes to a Microsoft Excel spreadsheet, reformat + export to a CSV file, then run a script on the CSV to quickly scrobble tracks with minimal fuss.

#### Unprofessional disclaimer and recognition this is likely not worth releasing under my name

* Not all scrobble options were added like album track # or total number of plays. I listen to an album at a time then remove it from my phone so... OCD types can chuck that together themselves.
* This documentation likely took longer to type than the actual script. Other than generating that `api_sig` checksum parameter, whatever the hell that's for. I'm authenticated and we're using ACK-strengthened TCP!
* You can generate a web form and nicely formatted result from the XML that Last.FM returns (not JSON!?) as I didn't care much for presentation, just functionality. Are you noticing a theme in this readme?
* I don't care about your precious not-iPhone smartphone. Not because I love Apple. I just had to get scrobbling working on the iPhone (that plays music) that I happen to own. I got it working. That was a good day.
* _What PSRs does this follow? Why are there global variables? Where are the separation of concerns? No composer.json? This is not extensible!_ Stuff it. This was a quick and dirty implentation for my own specific need. Fork and make useful as you see fit.

#### Script Setup

* You have a Last.FM account, right? If not, go here: http://www.last.fm/api/accounts
* Create an application to attain an API key and secret (shhhh): http://www.last.fm/api/accounts
* Paste those into `''` of `$apiKey` and `$secret` at the top of `lastfm-scrobble-csv.php`
* While logged into your Last.FM account in a web browser, generate a token:
  * Visit http://www.last.fm/api/auth/?api_key=xxx where `xxx` is the `api_key` generated in the previous step.
  * Copy the `token` parameter from resulting URL http://www.last.fm/api/auth/?token=yyy where `yyy` is your temporary session token.
* Paste into `''` of variable `$token` near the top of `lastfm-scrobble-csv.php`.
* Do you have PHP installed? If you're not sure, from Terminal on a Mac or Windows' command prompt, type `php`
  * Receive an error message that the command is not recognized or found? Install at least version 5.4 of PHP: http://php.net/downloads.php
* Run `php -f last-fm-scrobble-csv.php`and the expected output will be similar to:
  ```xml
  <lfm status="ok">
    <session>
      <name>MyLastFMUsername</name>
      <key>d580d57f32848f5dcf574d1ce18d78b2</key>
      <subscriber>0</subscriber>
    </session>
  </lfm>
  ```
* Copy that `<key>` and paste it into `$sessionId` near the top of `lastfm-scrobble-csv.php`.
* That was way too much work. Thanks, Last.FM!

#### Scrobbling

* Go to iTunes songs view and sort by column *Last Played* with the newest first.
* Use `Shift`+`Click` to highlight recent songs you want to scrobble and copy.
* Open Microsoft Excel, OpenOffice, LibreOffice, or whatever can create a CSV file.
* Paste that tabular data and insert a new empty row at the top.
* Add column headers that _must_ match these names (case-sensitive):
  * `track`
  * `duration`
  * `artist`
  * `albumArtist`
  * `album`
  * `timestamp`
  * `chosenByUser`
* `chosenByUser` for each row is `1` because you chose that song yourself because you're just a terrific person that doesn't listen to commerical radio. Onya.
* `Save as...` in your spreadsheet application and choose CSV.
* Go back to your terminal or command prompt and type `php -f lastfm-scrobble-csv.php whatever-file-your-just-saved.csv`
* Watch in amazement as XML is drawn in black and white to your screen. Or an error message is thrown and LOL I'm not going to help you.