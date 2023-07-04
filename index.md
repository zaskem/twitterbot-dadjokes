# Twitter "Dad Joke" Bot
___IMPORTANT NOTE:___ The Twitter "Dad Jokes" Bot was officially shut down in late June, 2023 due to Twitter's API changes. As a result this repository has been archived and under no further development.

The [dad joke bot](https://twitter.com/dadjoke_genbot) was developed in an afternoon-and-a-half as a novelty bot/project by [@zaskem](https://github.com/zaskem) on being inspired by the discovery of the [fatherhood.gov Dad Joke API](https://www.fatherhood.gov/for-dads/dad-jokes).

Technically this API isn't documented; h/t to [Matt Henry's discovery](https://twitter.com/heymatthenry/status/1370462717237153799).

The [live bot](https://twitter.com/dadjoke_genbot)'s data is sourced from the [fatherhood.gov Dad Joke API](https://www.fatherhood.gov/for-dads/dad-jokes) on demand (the full joke list refreshes no more than once daily). The bot monitors for new @-mentions containing the text `dad` and/or `joke` on a five minute interval. Newly discovered and matching tweets each receive a randomly-selected dad joke response.

For example, the following @-mention:

`Hey @dadjoke_genbot, tell me a dad joke!`

Will result in a tweet response like such:

```
.@matt_zaske, Hear about the guy that stayed up all night wondering where the sun had gone?

It finally dawned on him. #DadJokes
```

The [GitHub repo](https://github.com/zaskem/twitterbot-dadjokes) contains the basics for getting started with the bot in your own environment.

## Final update July, 2023
In late June, 2023, Twitter's new API access silently took effect, rendering the bot inactive by virtue of not allowing for Tweet searches. On June 28, 2023, the bot services were officially shut down and archived on the managing server. This repository has been archived and under no further development.

I have no intention of deleting the Twitter bot account itself, so past activity of the [dad joke bot](https://twitter.com/dadjoke_genbot) will live on as long as Twitter does (unless/until the account is deleted by Twitter).