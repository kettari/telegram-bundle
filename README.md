# Telegram Bundle
## Events
### Handle update cycle
  * **telegram.update.incoming** -- method handleUpdate() is called. 
  Event MUST return an Update object or throw an exception.
  * **telegram.update.received** -- when the Update object is ready.
    * 90000:LogSubscriber -- writes incoming log.
    * 80000:CurrentUserSubscriber -- finds current user using 
    UserHq class.
    * 70000:HookerSubscriber -- finds and executes hooks.
  * [TBD] **telegram.hook.before** -- before hook is executed.
  * [TBD] **telegram.hook.after** -- after hook is executed.
  * **telegram.message.received** -- when Message is found within the incoming Update.
    * 90000:IdentityWatchdogSubscriber -- updates User and Chat tables for the current user;
    adds default roles and permissions when user send his/her first message to the bot.
    * 0:MessageSubscriber
  * **telegram.text.received** -- when text is not empty within Message object.
  * **telegram.command.received** -- when a command is detected in the text, before it is executed.
  * **telegram.command.executed** -- after the command is executed.
  * **telegram.chatmember.joined** -- new chat member in the group.
  * **telegram.chatmember.left** -- chat member left the group.
  * **telegram.group.created** -- new group created.
  * **telegram.chat.migrated_to** -- migrated to chat ID.
  * **telegram.chat.migrated_from** -- migrated from chat ID.
  * [TBD] **telegram.response** -- when the Bot prepared a response to the Webhook and is ready to send it.
  * **telegram.request.sent** -- request to the Telegram API was sent.
     * LogSubscriber:-90000 -- writes outbound log.
  * **telegram.request.blocked** -- when bot is blocked by the user or kicked out of the group.
  * **telegram.request.throttled** -- flood control thrown an exception.
  * **telegram.request.exception** -- exception occurred while making request to the Telegram API.
  * **telegram.terminate** -- run any expensive operations when cycle is about to finish.
    
    