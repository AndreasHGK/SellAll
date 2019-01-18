# SellAll
Sell all items in your hand or all items in your inventory with the same type as in your hand
It's really simple: just type **/sell all** if you want to send every block in your inventory that is of the same type as in your hand. type **/sell hand** if you just want to sell the stack if items in your hand. Do **/sell inventory** to sell all sellable items in your inventory!

If you want to add a group of items to sell at once, like /sell ores to sell all the ores in your inventory, you can do this by adding a new group in the config.
You just have to list all the item IDs. The item names will work too but it's not recommended as they need to be exact.

The plugin will base the prices on what you put in **config.yml**. just add the id and then the price you want to sell it for. Example: `100: 10`
If you changed the config while the server is running you can do /sell reload. If you don't have perms it will look like the subcommand doesn't exist.

The plugin will do the rest! Players need the `sellall.command` permission to use the /sell command.
