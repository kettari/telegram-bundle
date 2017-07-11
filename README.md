# Telegram Bundle
## Configuration
  * `telegram.secret` -- arbitrary key that is not known to the public. This key is used in the
  `telegram.url` parameter when setting webhook. The idea is the resulting URL is known only to you and
  Telegram server so you can trust updates you receive at your endpoint. Good idea is to
   make it quite long ([0-9a-z]{32} for example).
  * `telegram.token` -- Telegram bot API key you got from the BotFather.
  * `telegram.certificate_file` -- open part of the certificate to send to Telegram server
  when registering webhook. This might be a self-signed certificate, see [Telegram documentation](https://core.telegram.org/bots/self-signed).
  * `telegram.url` -- a URL of this bot with a `{secret}` substring in it. For example:
  `https://www.your-domain.com/api/v1/{secret}/webhook`
  * `telegram.self_user_id` -- Telegram „user ID“ of the bot. It is required to distinguish
  the bot itself on chat join/left events.
  
## Events
### Handle update cycle
  * `telegram.update.incoming` -- method handleUpdate() is called. 
  Event MUST return an Update object or throw an exception.
    * 0:FilterSubscriber -- scraps inbound data.
  * `telegram.update.received` -- when the Update object is ready.
    * 90000:AuditSubscriber -- writes the incoming log.
    * 80000:CurrentUserSubscriber -- finds current user using 
    UserHq class.
    * 70000:HookerSubscriber -- finds and executes hooks.
  * [TBD] `telegram.hook.before` -- before hook is executed.
  * [TBD] `telegram.hook.after` -- after hook is executed.
  * `telegram.message.received` -- when Message is found within the incoming Update.
    * 90000:IdentityWatchdogSubscriber -- updates User and Chat tables for the current user;
    adds default roles and permissions when user send his/her first message to the bot.
    * 0:MessageSubscriber -- if text is not empty, dispatch further events.
    * –90000:MessageSubscriber -- checks if request was handled. If not, sends to the user a message.
  * `telegram.text.received` -- when text is not empty within Message object.
    * 90000:ChatMemberSubscriber -- updates ChatMember table.
    * 80000:TextSubscriber -- checks for command and dispatches further events.
  * `telegram.command.received` -- when a command is detected in the text.
    * 0:CommandSubscriber -- CommandBus service which executes command.
  * `telegram.command.unknown` -- command is unknown for CommandBus.
    * 0:CommandSubscriber -- tells the user that command is unknown.
  * `telegram.command.unauthorized` -- user has insufficient permissions.
    * 0:CommandSubscriber -- tells the user he or she is not authorized to execute the command.
  * `telegram.command.executed` -- after the command is executed.
  * `telegram.user.registered` -- when /register command executed and user finished registration.
  * `telegram.chatmember.joined` -- new chat member in the group.
    * 0:ChatMemberSubscriber -- handles new chat member.
  * `telegram.chatmembers.joined` -- one or more new chat members in the group.
  * `telegram.chatmember.bot_joined` -- the bot itself joined the group.
  * `telegram.chatmember.left` -- chat member left the group.
    * 0:ChatMemberSubscriber -- handles left chat member.
  * `telegram.chatmember.bot_left` -- the bot itself left the group.
  * `telegram.group.created` -- new group created.
    * 0:GroupsSubscriber -- handles new group creation.
  * `telegram.chat.migrated_to` -- migrated to chat ID.
    * 0:MigrationSubscriber -- handles group migration.
  * `telegram.chat.migrated_from` -- migrated from chat ID.
    * 0:MigrationSubscriber -- handles group migration.
  * [TBD] `telegram.response` -- when the bot prepared a response to the Webhook and is ready to send it.
  * `telegram.request.sent` -- request to the Telegram API was sent.
     * –90000:AuditSubscriber -- writes outbound log.
  * `telegram.request.blocked` -- when the bot is blocked by the user or kicked out of the group.
  * `telegram.request.throttled` -- flood control thrown an exception.
  * `telegram.request.exception` -- exception occurred while making request to the Telegram API.
  * `telegram.terminate` -- run any expensive operations when cycle is about to finish.
    
    