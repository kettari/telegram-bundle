# Telegram Bundle
## Events
### Handle update cycle
  * **telegram.update.incoming** -- method handleUpdate() is called. 
  Event MUST return an Update object or throw an exception.
    * 0:FilterSubscriber -- scraps inbound data.
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
    * 0:MessageSubscriber -- if text is not empty, dispatch further events.
    * –90000:MessageSubscriber -- checks if request was handled. If not, sends to the user a message.
  * **telegram.text.received** -- when text is not empty within Message object.
    * 90000:TextSubscriber -- checks for command and dispatches further events.
     * 0:ChatMemberSubscriber -- updates ChatMember table.
  * **telegram.command.received** -- when a command is detected in the text.
    * 0:CommandSubscriber -- CommandBus service which executes command.
  * **telegram.command.unknown** -- command is unknown for CommandBus.
    * 0:CommandSubscriber -- tells the user that command is unknown.
  * **telegram.command.unauthorized** -- user has insufficient permissions.
    * 0:CommandSubscriber -- tells the user he or she is not authorized to execute the command.
  * **telegram.command.executed** -- after the command is executed.
  * **telegram.chatmember.joined** -- new chat member in the group.
    * 0:ChatMemberSubscriber -- handles new chat member.
  * **telegram.chatmember.left** -- chat member left the group.
    * 0:ChatMemberSubscriber -- handles left chat member.
  * **telegram.group.created** -- new group created.
    * 0:GroupsSubscriber -- handles new group creation.
  * **telegram.chat.migrated_to** -- migrated to chat ID.
    * 0:MigrationSubscriber -- handles group migration.
  * **telegram.chat.migrated_from** -- migrated from chat ID.
    * 0:MigrationSubscriber -- handles group migration.
  * [TBD] **telegram.response** -- when the bot prepared a response to the Webhook and is ready to send it.
  * **telegram.request.sent** -- request to the Telegram API was sent.
     * –90000:LogSubscriber -- writes outbound log.
  * **telegram.request.blocked** -- when the bot is blocked by the user or kicked out of the group.
  * **telegram.request.throttled** -- flood control thrown an exception.
  * **telegram.request.exception** -- exception occurred while making request to the Telegram API.
  * **telegram.terminate** -- run any expensive operations when cycle is about to finish.
    
    