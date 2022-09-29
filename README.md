<p align="center"><img src="https://i.ibb.co/ckmrTb0/bt.png"></p>  

<p align="center">
  <a href="https://www.paypal.com/donate/?hosted_button_id=SRQH6M2S6LV6Y">
    <img src="https://img.shields.io/badge/donate-paypal-ff69b4?style=for-the-badge&logo=paypal">  
  </a>
  <a href="https://poggit.pmmp.io/ci/CzechPMDevs/BuilderTools/BuilderTools">  
    <img src="https://poggit.pmmp.io/ci.shield/CzechPMDevs/BuilderTools/BuilderTools?style=for-the-badge">  
  </a>  
  <a href="https://discord.gg/uwBf2jS">  
    <img src="https://img.shields.io/discord/365202594932719616.svg?style=for-the-badge&color=7289da&logo=discord&logoColor=white&logoWidth=12">  
  </a>
  <a href="https://poggit.pmmp.io/p/BuilderTools">  
    <img src="https://poggit.pmmp.io/shield.downloads/BuilderTools?style=for-the-badge">  
  </a>  
<br><br>  
   ✔️ Advanced WorldEdit commands
    <br>  
    ✔️ Using Sub Chunk Iterator to make block placing faster
    <br>  
    ✔️ Supports schematics
    <br>  
    ✔️ Minecraft: Java Version maps world fixer  
    <br>  
    ✔️ Supports last PocketMine API version  
    <br><br>  
</p>  

## 👍 2.0.0 Update:

- PocketMine 5.0 support
- More selection types
- Supporting NBT structure schematics

## ⬇️ Downloads:

| Downloads                                         | API       | Downloads                                                                     |
|---------------------------------------------------|-----------|-------------------------------------------------------------------------------|
| Latest Stable Release (1.3.1)                     | 4.x       | [Poggit](https://poggit.pmmp.io/p/BuilderTools/1.3.1)                         |
| Latest Beta Release (1.4.0-beta2)                 | 4.x       | [Poggit](https://poggit.pmmp.io/r/184935/BuilderTools.phar)                   |
| Latest Dev Build  (1.4.0 for 4.x / 2.0.0 for 5.x) | 4.x / 5.x | [Poggit CI](https://poggit.pmmp.io/ci/CzechPMDevs/BuilderTools/BuilderTools/) |

<br>  

> **All released versions [here](https://github.com/CzechPMDevs/BuilderTools/releases)**  
> **Other plugins by CzechPMDevs [here](https://poggit.pmmp.io/plugins/by/CzechPMDevs)**

<br>

## 💬 FAQ

- `Required extension Core has an incompatible version (7.* not >=7.4)`
    - Your server is using outdated PHP version. If you host your server by yourself, update your php
      binaries [More information](https://pmmp.readthedocs.io/en/rtfd/installation/installing-manually.html#getting-php-for-your-server)
      . If you are using some host provider, contact them to update their php version.

## 🔧 Installing the plugin

1) [Download](https://poggit.pmmp.io/p/BuilderTools) the latest stable version from poggit
2) Move downloaded file to your server **/plugins/** folder
3) Restart the server

## 🏠 BuilderTools commands

- All BuilderTools commands starts with `//`except for the `/buildertools` command that was added as an alias
  because `//help` not works in newer versions.
- In game, you can get list of all commands using commands `//commands`

<br>  

**Commands**

| **Command**          | **Description**                                                                                                                                                                                                                                                                |
|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **//commands**       | **Displays list BuilderTools commands** <br><br> Alias: `//commands`, `/buildertools` <br>Usage: `//commands <page: 1-4>`                                                                                                                                                      |
| **//biome**          | **Changes biome in selected area** <br><br> Usage: `//biome list` OR `//biome <biomeId>`                                                                                                                                                                                       |
| **//blockinfo**      | **Switch block info mode** <br><br>Usage: `//blockinfo`<br>Alias: `//bi`<br><br>In blockinfo mode you can get information about block by touching it.                                                                                                                          |
| **//clearinventory** | **Clears inventory** <br><br>Usage: `//clearinventory`<br>Alias: `//ci`                                                                                                                                                                                                        |
| **//center**         | **Finds center of the selection** <br><br> Usage: `//center` <br><br> Bedrock will appear in the middle of the selection                                                                                                                                                       |
| **//copy**           | **Copy selected area into the clipboard** <br><br> Usage: `//copy` <br><br> Copied area can be placed again using `//paste`, merged with the environment `//merge` or rotated `//rotate`.                                                                                      |
| **//cube**           | **Creates cube** <br><br> Usage: `//cube <id1:dmg1,id2,...> <radius>` <br><br> Creates a cube at your position.                                                                                                                                                                |
| **//cut**            | **Cuts out selected area** <br><br> Usage: `//cube <id1:dmg1,id2,...> <radius>` <br><br> The are is afterwards moved on to clipboard.                                                                                                                                          |
| **//cylinder**       | **Creates cylinder** <br><br> Usage: `//cube <id1:dmg1,id2,...> <radius>` <br><br> Creates a cylinder at your position.                                                                                                                                                        |
| **//draw**           | **Draws with blocks** <br><br>Usage: `//draw <cube or sphere or off> [brush: 1-6] [fall = false]` <br><br> We recommend to use this command while creating big mountains. Draw mode is turned on by typing `//draw <cube or sphere>` and can be turned of typing `//draw off`. |
| **//fill**           | **Fill selected area** <br><br> Aliases: `//set`, `//change` <br> Usage: `//fill <id1:dmg1,id2,...>` <br><br> First you must create area using `//pos1`, `//pos2` or by `//wand`.                                                                                              |
| **//fix**            | **Fixes block in world from Minecraft: Java Edition** <br><br> Usage: `//fix <world>`                                                                                                                                                                                          |  
| **//flip**           | **Flips selection** <br><br>Usage `//flip x` OR `//flip y` OR `//flip z`<br><br>The argument represents axis to flip the selection through.                                                                                                                                    |
| **//hcube**          | **Creates hollow cube** <br><br>Usage: `//hcube <id1:dmg1,id2,...> <radius>`<br><br>Creates hollow cube at your position.                                                                                                                                                      |  
| **//hcylinder**      | **Creates hollow cylinder** <br><br>Usage: `//hcyl <id1:dmg1,id2,...> <radius>`<br><br>Creates hollow cylinder at your position.                                                                                                                                               |
| **//hpyramid**       | **Creates hollow pyramid** <br><br>Usage: `//hpyramid <id1:dmg1,id2,...> <radius>`<br><br>Creates hollow pyramid at your position.                                                                                                                                             |
| **//hsphere**        | **Creates hollow sphere** <br><br>Usage: `//hsphere <id1:dmg1,id2,...> <radius>`<br><br>Creates hollow sphere at your position.                                                                                                                                                |
| **//id**             | **Displays id of item in your hand** <br><br>Usage: `//id`                                                                                                                                                                                                                     |
| **//merge**          | **Merge copied area** <br><br> Usage: `//merge`                                                                                                                                                                                                                                |
| **//move**           | **Move blocks in selection** <br><br>Usage: `//move <x> <y> <z>`<br><br>Move blocks in selected area.                                                                                                                                                                          |
| **//naturalize**     | **Replaces blocks in selected area to grass,dirt and stone** <br><br>Usage: `//naturalize`                                                                                                                                                                                     |
| **//outline**        | **Fills hollow selected area** <br><br>Usage: `//outline <id1:dmg1,id2,...>`<br><br>Changes the all the outer layers.                                                                                                                                                          |
| **//paste**          | **Paste copied area** <br><br> Usage: `//paste`                                                                                                                                                                                                                                |
| **//pos1**           | **Select first position** <br><br> Aliases: `//1`, `//pos1` <br> Usage: `//pos1` <br><br> You need select two positions for eg. filling or fixing Minecraft: Java Edition maps.                                                                                                |
| **//pos2**           | **Select second position** <br><br> Aliases: `//2`, `//pos2` <br> Usage: `//pos2` <br><br> You need select two positions for eg. filling or fixing Minecraft: Java Edition maps.                                                                                               |
| **//pyramid**        | **Creates pyramid** <br><br>Usage: `//pyramid <id1:dmg1,id2,...> <radius>`<br><br>Creates pyramid in your position.                                                                                                                                                            |
| **//redo**           | **Re-do BuilderTools action** <br><br> Usage: `//redo`                                                                                                                                                                                                                         |
| **//replace**        | **Replace blocks in selected area** <br><br> Usage: `//replace <blocksToReplace: id1,id2> <blocks: id1:dmg1,id2,...>` <br><br> Replace blocks in selected area. First you must create area using `//pos1`, `//pos2` or by `//wand`.                                            |
| **//rotate**         | **Rotate copied area** <br><br>Usage: `//rotate <y> [x] [z]` <br><br> Y, X or Z is axis you can rotate object around. Use degrees as unit. Example: `//rotate 90`                                                                                                              |
| **//schematic**      | **Manage with schematics** <br><br>Usage: `//schem <reload OR load OR list OR paste> [filename]`<br><br>Manage with schematics (reload - loads all schematics to memory; load - loads schematics for //schem paste; list - displays list of loaded schematics.                 |
| **//sphere**         | **Creates sphere** <br><br> Usage: `//sphere <id1:dmg1,id2,...> <radius>` <br><br> Creates a sphere in your position.                                                                                                                                                          |
| **//stack**          | **Stacks copied area** <br><br>Usage: `//stack <count> [side or up or down]`<br><br>Stacks blocks in line.                                                                                                                                                                     |
| **//tree**           | **Spawns tree** <br><br> Usage: `//tree <tree OR list>` <br><br> There are implemented only basic trees (`oak`, `spruce`, `jungle` and `birch`)                                                                                                                                |
| **//undo**           | **Cancels BuilderTools action** <br><br> Usage: `//undo`                                                                                                                                                                                                                       |
| **//wand**           | **Switch wand tool** <br><br> Usage: `//wand` <br><br> First position is set  by breaking the block, second by touching the block. Wand tool can be turned of typing `//wand` again.                                                                                           |


<br>

## 🛠️ Other features:

### 📜 Schematics

- You can save your selection to a file and then load it again
- BuilderTools supports loading [MCEdit](https://minecraft.fandom.com/wiki/Schematic_file_format)
  and [MCStructure](https://minecraft.fandom.com/wiki/Structure_Block#Save) formats and creating schematics wth MCEdit
  format.
- Schematics are loaded asynchronously, that means it won't lag server while loading.

#### Loading schematics:

1) Move schematic file to `/plugin_data/BuilderTools/schematics` directory
2) Load schematic using `//schem load <schematic>`
3) Paste into the world using `//schem paste <schematic>`

#### Creating schematics:

1) Select two positions using `//pos1` & `//pos2` commands or using wand axe.
2) Use `//schem create <schematicName>`
3) File will be saved in directory `/plugin_data/BuilderTools/schematics/schematicName.schematic`

### 🌎 Fixing Java: Edition worlds

- BuilderTools is able to fix block ids in worlds generated by Minecraft: Java Edition.
- Currently, we support only [Anvil](https://minecraft.fandom.com/wiki/Anvil_file_format) world format
- To fix a world use `//fix <worldName>`
- Worlds are fixed asynchronously, so the process will not freeze server. Players are able to play while fixing world,
  but are not able to join the world, which is being fixed.

## 📃 Permissions

<br>  

**All BuilderTools Permissions:**

| Permission                          | Command            | Operator Permissions required |
|-------------------------------------|--------------------|-------------------------------|
| buildertools.command.help           | `//commands`       | ✔️                            |
| buildertools.command.biome          | `//biome`          | ✔️                            |
| buildertools.command.blockinfo      | `//blockinfo`      | ✔️                            |  
| buildertools.command.clearinventory | `//clearinventory` | ✔️                            |  
| buildertools.command.copy           | `//copy`           | ✔️                            |  
| buildertools.command.cube           | `//cube`           | ✔️                            |
| buildertools.command.cut            | `//cut`            | ✔️                            |
| buildertools.command.cylinder       | `//cylinder`       | ✔️                            |
| buildertools.command.decoration     | `//decoration`     | ✔️                            |
| buildertools.command.draw           | `//draw`           | ✔️                            |  
| buildertools.command.fill           | `//fill`           | ✔️                            |  
| buildertools.command.fix            | `//fix`            | ✔️                            |
| buildertools.command.hcube          | `//hcube`          | ✔️                            |  
| buildertools.command.hcylinder      | `//hcylinder`      | ✔️                            |  
| buildertools.command.hpyramid       | `//hpyramid`       | ✔️                            |  
| buildertools.command.hsphere        | `//hsphere`        | ✔️                            |  
| buildertools.command.id             | `//id`             | ✔️                            |  
| buildertools.command.merge          | `//merge`          | ✔️                            |  
| buildertools.command.move           | `//move`           | ✔️                            |  
| buildertools.command.naturalize     | `//naturalize`     | ✔️                            |  
| buildertools.command.outline        | `//outline`        | ✔️                            |  
| buildertools.command.paste          | `//paste`          | ✔️                            |  
| buildertools.command.pos1           | `//pos1`           | ✔️                            |  
| buildertools.command.pos2           | `//pos2`           | ✔️                            |  
| buildertools.command.pyramid        | `//pyramid`        | ✔️                            |  
| buildertools.command.rotate         | `//rotate`         | ✔️                            |  
| buildertools.command.schematic      | `//schematic`      | ✔️                            |  
| buildertools.command.sphere         | `//sphere`         | ✔️                            |  
| buildertools.command.stack          | `//stack`          | ✔️                            |  
| buildertools.command.tree           | `//tree`           | ✔️                            |  
| buildertools.command.undo           | `//undo`           | ✔️                            |
| buildertools.command.walls          | `//walls`          | ✔️                            |
| buildertools.command.wand           | `//wand`           | ✔️                            |  

## 🔧 Configuration

- Default configuration:

```yaml  
# BuilderTools configuration file
# Target BuilderTools version: 1.3.0

# Do not change this line.
config-version: 1.3.0.0

# This is format which will be used for creating schematics
# Supported formats: 'mcedit', 'mcstructure', 'buildertools'
output-schematics-format: 'mcedit'

# Option for compressing clipboards. This will make the actions
# slower, but reduces RAM usage.
clipboard-compression: true

# Some shapes are generated with duplicate blocks. This problem causes
# some blocks are not reverted right when doing //undo. This option
# is for removing duplicates.
# Warning: This action takes around 98% time of the whole process!
remove-duplicate-blocks: true

# BuilderTools saves player's clipboard, undo & redo stuff when player
# leaves server to disk. This cache should be cleaned after restart (to
# avoid unexpected bugs). This  option is to disable removing those files.
clean-cache: true

# When player leaves the server, player's session is saved, even if player did not
# do any action with BuilderTools. If you enable this, player's sessions will not
# be saved and when player joins the server again, his clipboard data will be lost
# This option is good when BuilderTools is only used as api plugin on server with
# high amount of players.
discard-sessions: false

# PowerItems settings:

# When disabled, //wand command still works, but instead of wand axe is hand
# going to be the 'wand tool'
wand-axe:
  enabled: true
  name: "§r§fWand Axe\n§7§oBreak for first pos\n§7§oTouch for second pos"

blockinfo-stick:
  enabled: false
  name: "§r§fDebug Stick\n§7§oTouch block for info"
```

<br>

## 💰 Credits

- Icon made by [Freepik](http://www.freepik.com/ "Freepik")
  from [www.flaticon.com](https://www.flaticon.com/ "Flaticon") is licensed
  by [CC 3.0 BY](http://creativecommons.org/licenses/by/3.0/ "Creative Commons BY 3.0")
- Seabuild spawn built by CryptoKey

<br>

## 💡 License

```  
Copyright 2018-2022 CzechPMDevs    
    
Licensed under the Apache License, Version 2.0 (the "License");    
you may not use this file except in compliance with the License.    
You may obtain a copy of the License at    
   
https://www.apache.org/licenses/LICENSE-2.0    
   
Unless required by applicable law or agreed to in writing, software    
distributed under the License is distributed on an "AS IS" BASIS,    
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.    
See the License for the specific language governing permissions and    
limitations under the License.  
```  

Full license [here](https://github.com/CzechPMDevs/BuilderTools/blob/master/LICENSE).
