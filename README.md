# Telegram Bundle
## Events
  * **telegram.update.incoming** -- when method handleUpdate() is called. 
  Event MUST return an Update object or throw exception.
  * **telegram.update.received** -- when the Update object is ready.
    * 90000:LogSubscriber -- writes incoming log.
    * 89000:CurrentUserSubscriber -- finds current user using 
    UserHq class.
    * 88000:HookerSubscriber -- finds and executes hooks.
  * **telegram.message.received** -- when Message found within incoming Update.
    * 90000:IdentityWatchdogSubscriber -- updates User and Chat tables for the current user;
    adds default roles and permissions when user send his/her first message to the bot.
    * 0:MessageSubscriber