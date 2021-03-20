# Twitter "Dad Jokes" Bot
A [novelty bot](https://twitter.com/dadjoke_genbot) written in PHP to reply to @-mentions requesting "dad jokes" with a randomly-generated dad joke.

Data is sourced from the [fatherhood.gov](https://www.fatherhood.gov/for-dads/dad-jokes) Dad Joke API. Technically this API isn't documented; h/t to [Matt Henry's discovery](https://twitter.com/heymatthenry/status/1370462717237153799).

## Requirements
To run the bot code, the following libraries/accounts/things are required:

* A bot/user account on Twitter for tweets;
* A project and app configured on the [Twitter Developer Portal](https://developer.twitter.com/);
* A manner by which you can generate an OAuth token and grant permission to the app for the bot account as necessary; and
* A host on which to run this code (not at a browsable path).

### [Fatherhood.gov Dad Joke API](https://www.fatherhood.gov/for-dads/dad-jokes)
The [fatherhood.gov Dad Joke API](https://www.fatherhood.gov/for-dads/dad-jokes) is accessed via a very simple cURL request. As the underlying data _rarely_ changes it's only pinged up to once per `$dadJokeDataExpires` seconds as set in `config/bot.php`. The bot code default is 24 hours.

### Twitter API
Applying for access to the [Twitter Developer Portal](https://developer.twitter.com/) is outside the scope of this README. You will need to create a new Project and/or App for the Twitter bot and configure the App permissions to allow `Read and Write` access. You will obtain the `consumer_key` and `consumer_secret` from the App's keys and tokens page.

Assuming the account associated with the Developer Portal is _not_ the bot account, you will need to enable `3-Legged OAuth` for the App. This is required to generate a user access token and secret for an independent bot account.

#### A note about generating user access tokens and secrets:
This repo does not include a library/mechanism to address user access and callback for the bot app, which is ___required___ to generate a user access token and secret, and is generally a one-time action. It is recommended to use [Twurl](https://developer.twitter.com/en/docs/tutorials/using-twurl) for its simplicity. The following steps on a local WSL or Ubuntu instance (independent of the bot host if necessary) will generate the token and secret:

1. `gem install twurl` (to install Twurl, also requires ruby)
2. `twurl authorize --consumer-key key --consumer-secret secret` (with your Twitter App key/secret, follow prompts)
3. `more ~/.twurlrc` (to obtain the bot account `token` and `secret` values)

## Bot Configuration
A single configuration file resides in the `config/` directory. An example version is provided, and to get started should be copied without the `.example` extension (e.g. `cp bot.php.example bot.php`)

Edit the `bot.php` file as necessary for your configuration.

The Dad Joke API cache file and a status file identifying information for the last tweet mention will be auto-generated at the paths specified in the variable declarations `$dadJokeDataFile` and `$lastTweetFile` respectively. These files are auto-generated and managed; no editing or interaction/cleanup is necessary.

## Bot Usage, Tweeting Replies, and Crontab Setup
The bot process has been designed to run on a periodic interval to poll Twitter for new mentions (versus maintain an open stream). The bot will check for new mentions, grab a random dad joke, and reply to qualifying tweets by invoking a simple command:
`php TweetDadJokes.php`

The bot _**will only respond to @-mention tweets containing the text `dad` and/or `joke`**_ (case insensitive). Other mentions and tweets are ignored.

Cron should be used for production. A simple default crontab setting might look like this:
```bash
*/5 * * * * /path/to/php /path/to/TweetDadJokes.php
```
The above will run the bot script every 5 minutes, which would responsibly query Twitter for a low-volume bot (as is designed) and provide a reasonable reply turnaround. High-volume bots/requests would be best served with the [Twitter Streaming API](https://developer.twitter.com/en/docs/twitter-api/v1/tweets/filter-realtime/overview); this bot code is not designed for said service.

## Triggering a Response
Have any Twitter account Tweet @ your bot where the text contains the trigger words (`dad` and/or `joke`). For example, your tweet might look like:

`Hey @dadjoke_genbot, tell me a dad joke!`

At the next trigger of `TweetDadJokes.php` (see crontab example above), this mention will be identified by the bot code and an appropriate dad joke will be in the bot's response.

### A Note of Caution
Even when testing, it is _highly discouraged_ to have the bot @-mention itself for a dad joke. This will create a loop condition where each trigger of `TweetDadJokes.php` will respond, creating a never-ending thread until the latest responses (or the thread(s)) are deleted. This behavior isn't outright blocked, however, as it can be useful during initial testing. You have been warned.

## Troubleshooting and Tweet Posting
This bot doesn't have a lot of moving parts, so there's not a lot to troubleshoot. There are three general points of failure:

* Failure to source dad joke data; 
* Failure to obtain mentions from Twitter; and
* Failure to post a tweet response.

Setting `$debug_bot` to `true` in `config/bot.php` will output some information about the process and possible failures. If a `"Bad Request"` status shows up, there is likely a Twitter API problem, likely related to credentials/keys/secrets.

## Contributors
Developed in an afternoon-and-a-half as a novelty bot/project by [@zaskem](https://github.com/zaskem) on being inspired by discovering the Dad Joke API.
