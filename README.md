
<a align="center"><img src="https://i.ibb.co/swwgvRV/n.png"></a>

<p align="center">
	<a href="https://poggit.pmmp.io/ci/CzechPMDevs/BuilderTools/BuilderTools">
		<img src="https://poggit.pmmp.io/ci.shield/CzechPMDevs/BuilderTools/BuilderTools?style=flat-square">
	</a>
	<a href="https://discord.gg/uwBf2jS">
		<img src="https://img.shields.io/discord/365202594932719616.svg?style=flat-square">
	</a>
	<a href="https://github.com/CzechPMDevs/BuilderTools/releases">
		<img src="https://img.shields.io/github/release/CzechPMDevs/BuilderTools.svg?style=flat-square">
	</a>
	<a href="https://github.com/CzechPMDevs/BuilderTools/releases">
		<img src="https://img.shields.io/github/downloads/CzechPMDevs/BuilderTools/total.svg?style=flat-square">
	</a>
	</a>
	<a href="https://github.com/CzechPMDevs/BuilderTools/blob/master/LICENSE">
		<img src="https://img.shields.io/github/license/CzechPMDevs/BuilderTools.svg?style=flat-square">
	</a>
	<a href="https://poggit.pmmp.io/p/BuilderTools">
		<img src="https://poggit.pmmp.io/shield.state/BuilderTools?style=flat-square">
	</a>
<br><br>
	✔️ Advanced WorldEdit commands
    <br>
    ✔️ Supports fast filling
    <br>
    ✔️ Schematics support
    <br>
    ✔️Simple hill making
    <br>
    ✔️ Minecraft: Java Version maps world fixer
    <br>
    ✔️ Supports last pocketmine api version
    <br><br>
</p>

<p align="center">1.2 Release date: 16.3. 2019</p>

## 👍 1.2 Update:
> - Plugin cleanup
> - Lots of new commands
> - Plugin now supports 4.0.0 api versions too
> - Bug fixes
> - Schematics support



## ⬇️ Downloads:

| Version | Phar Download | Zip Download | API | Stable | Pre release |
| --- | --- | --- | --- | --- | --- | 
| 1.2.0-beta1 | - | - | 3.x.x, 4.x.x | ✔️ | ✔️ |
| 1.1.0 | [GitHub](https://github.com/CzechPMDevs/BuilderTools/releases/download/1.1.0/BuilderTools_v1.1.0.phar) | [GitHub](https://github.com/CzechPMDevs/BuilderTools/archive/1.1.0) | 3.x.x | ✔️ | ❌ |
| 1.1.0-beta2 | [GitHub](https://github.com/CzechPMDevs/BuilderTools/releases/download/1.1.0-beta2/BuilderTools_v1.1.0-beta2.phar) | [GitHub](https://github.com/CzechPMDevs/BuilderTools/archive/1.1.0-beta1) | 3.x.x | ✔️ | ✔️|
| 1.1.0-beta1 | [GitHub](https://github.com/CzechPMDevs/BuilderTools/releases/download/1.1.0-beta1/BuilderTools_v1.1.0-beta1.phar) | [GitHub](https://github.com/CzechPMDevs/BuilderTools/archive/1.1.0-beta1) | 3.x.x | ❌ | ✔️|
| 1.0.0 | [GitHub](https://github.com/CzechPMDevs/BuilderTools/releases/download/1.0.0/BuilderTools.phar) | [GitHub](https://github.com/CzechPMDevs/BuilderTools/archive/1.0.0.zip) | 3.0.0-ALPHA7 | ✔️ | ❌|

<br>

> **All released versions [here](https://github.com/CzechPMDevs/BuilderTools/releases)**
> **Other plugins by CzechPMDevs [here](https://poggit.pmmp.io/plugins/by/CzechPMDevs)**

<br>

## 📁 Supported software:

**This plugin works only on PocketMine-MP.**


## 🔧 How to install BuilderTools?

1) [Download](https://poggit.pmmp.io/ci/CzechPMDevs/BuilderTools/~) latest stable version from poggit
2) Move dowloaded file to your server **/plugins/** folder
3) Restart the server

## 🏠 BuilderTools commands:

- All BuilderTools commands starts with `//`except for the `/buildertools` command that was added as an alias because `//help` not works in newer versions.
- In game, you can get list of all commands using commands `//commands`

<br>

**All BuilderTools Commands:**

| **Command** | **Description** |
| --- | --- |
| **//commands** | **Displays list BuilderTools commands** <br><br> Alias: `//commands`, `/buildertools` <br>Usage: `//commands <page: 1-4>`|
| **//pos1** | **Select first position** <br><br> Aliases: `//1`, `//pos1` <br> Usage: `//pos1` <br><br> You need select two possitions for eg. filling or fixing Minecraft: Java Edition maps. |
| **//pos2** | **Select second position** <br><br> Aliases: `//2`, `//pos2` <br> Usage: `//pos2` <br><br> You need select two possitions for eg. filling or fixing Minecraft: Java Edition maps. |
| **//fill** | **Fill selected area** <br><br> Aliases: `//set`, `//change` <br> Usage: `//fill <id1:dmg1,id2,...>` <br><br> First you must create area using `//pos1`, `//pos2` or by `//wand`. |
| **//wand** |**Switch wand tool** <br><br> Usage: `//wand` <br><br> First position is set  by breaking the block, second by touching the block. Wand tool can be turned of typing `//wand` again.|
| **//sphere** | **Creates sphere** <br><br> Usage: `//sphere <id1:dmg1,id2,...> <radius>` <br><br> Creates a sphere in your position. |
| **//cube** | **Creates cube** <br><br> Usage: `//cube <id1:dmg1,id2,...> <radius>` <br><br> Creates a cube in your position. |
| **//replace** | **Replace blocks in selected area** <br><br> Usage: `//replace <blocksToReplace: id1,id2> <blocks: id1:dmg1,id2,...>` <br><br> Replace blocks in selected area. First you must create area using `//pos1`, `//pos2` or by `//wand`. |
| **//draw** | **Draws with blocks** <br><br>Usage: `//draw <cube|sphere|off> [brush: 1-6] [fall = false]` <br><br> We are recommend to use this command while creating big mountains. Draw mode is turned on by typing `//draw <cube|sphere>` and can be turned of typing `//draw off`. |
| **//copy** | **Copy selected area into the clipboard** <br><br> Usage: `//copy` <br><br> Copied area can be placed again using `//paste`, merged with the environment `//merge` or rotated `//rotate`.|
| **//paste** | **Paste copied area** <br><br> Usage: `//paste` |
| **//merge** | **Merge copied area** <br><br> Usage: `//merge` |
| **//rotate** | **Rotate copied area** <br><br>Usage: `//rotate` <br><br> When rotating an object, you must rotate to the side to which you want to rotate the object, and then write the `confirm` to the chat. If you want to cancel rotation, type `cancel` into the chat.|
| **//flip** | **Flip copied area** <br><br> Usage: `//flip` <br><br> Rotate copied area upside down.|
| **//undo** | **Cancels BuilderTools action** <br><br> Usage: `//undo` |
| **//fix** | **Fixes blocks from Minecraft: Java Edition** <br><br> Usage: `//fix` <br><br> First you must create area using `//pos1`, `//pos2` or by `//wand`.|
| **//tree** | **Spawns tree** <br><br> Usage: `//tree <tree|list>` <br><br> There are implemented only basic trees (`oak`, `spruce`, `jungle` and `birch`) |
| **//naturalize** | **Replaces blocks in selected area to grass,dirt and stone** <br><br>Usage: `//naturalize` |
| **//id** | **Displays id of item in your hand** <br><br>Usage: `//id` |
| **//clearinventory** | **Clears inventory** <br><br>Usage: `//clearinventory`<br>Alias: `//ci`  |
| **//blockinfo** | **Switch block info mode** <br><br>Usage: `//blockinfo`<br>Alias: `//bi`<br><br>In blockinfo mode you can get information about block by touching it.  |
| **//hsphere** | **Creates hollow sphere** <br><br>Usage: `//hsphere <id1:dmg1,id2,...> <radius>`<br><br>Creates hollow sphere in your position.  |
| **//hcube** | **Creates hollow cube** <br><br>Usage: `//hcube <id1:dmg1,id2,...> <radius>`<br><br>Creates hollow cube in your position.  |
| **//pyramid** | **Creates pyramid** <br><br>Usage: `//pyramid <id1:dmg1,id2,...> <radius>`<br><br>Creates pyramid in your position.  |
| **//hpyramid** | **Creates hollow pyramid** <br><br>Usage: `//hpyramid <id1:dmg1,id2,...> <radius>`<br><br>Creates hollow pyramid in your position.  |
| **//stack** | **Stacks copied area** <br><br>Usage: `//stack <count> [side|up|down]`<br><br>Stacks blocks in line.  |
| **//outline** | **Fills holow selected area** <br><br>Usage: `//outline <id1:dmg1,id2,...>`<br><br>Fills holow selected area.  |
| **//move** | **Move blocks** <br><br>Usage: `//move <x> <y> <z>`<br><br>Move blocks in selected area.  |
| **//schematic** | **Manage with schematics** <br><br>Usage: `//schem <reload|load|list|paste> [filename]`<br><br>Manage with schematics (reload - loads all schematics to memory; load - lodads schematics for //schem paste; list - displays list of loaded schematics.  |



## 📃  Permissions:

<br>

**All BuilderTools Permissions:**



| Permission | Command | Opertor |
| --- | --- | --- |
| bt.cmd.help | `//commands` | ✔️ |
| bt.cmd.pos1 | `//pos1` | ✔️ |
| bt.cmd.pos2 | `//pos2` | ✔️ |
| bt.cmd.fill | `//fill` | ✔️ |
| bt.cmd.wand | `//wand` | ✔️ |
| bt.cmd.sphere | `//sphere` | ✔️ |
| bt.cmd.cube | `//cube` | ✔️ |
| bt.cmd.draw | `//draw` | ✔️ |
| bt.cmd.copy | `//copy` | ✔️ |
| bt.cmd.paste | `//paste` | ✔️ |
| bt.cmd.merge | `//merge` | ✔️ |
| bt.cmd.rotate | `//rotate` | ✔️ |
| bt.cmd.flip | `//flip` | ✔️ |
| bt.cmd.undo | `//undo` | ✔️ |
| bt.cmd.fix | `//fix` | ✔️ |
| bt.cmd.tree | `//tree` | ✔️ |
| bt.cmd.naturalize | `//naturalize` | ✔️ |
| bt.cmd.id | `//id` | ✔️ |
| bt.cmd.clearinventory | `//clearinventory` | ✔️ |
| bt.cmd.blockinfo | `//blockinfo` | ✔️ |
| bt.cmd.hsphere | `//hsphere` | ✔️ |
| bt.cmd.hcube | `//hcube` | ✔️ |
| bt.cmd.schematic | `//schematic` | ✔️ |
| bt.cmd.cylinder | `//cylinder` | ✔️ |
| bt.cmd.hcylinder | `//hcylinder` | ✔️ |
| bt.cmd.pyramid | `//pyramid` | ✔️ |
| bt.cmd.hpyramid | `//hpyramid` | ✔️ |
| bt.cmd.stack | `//stack` | ✔️ |
| bt.cmd.outline | `//outline` | ✔️ |
| bt.cmd.move | `//move` | ✔️ |
| bt.cmd.schematic | `//schematic` | ✔️ |

## 🔧 Configuration
- New in 1.2
- Default configuration:
```yaml
---  
# \  
## > BuilderTools configuration file  
# /  
  
# Do not change this!  
config-version: 1.2.0  
  
schematics:  
  # Available modes: startup, oncommand  
 #  - startup - when plugin starts, all schematics will loaded #  - oncommand - schematics will been loaded when player use '//schem load' command  load: startup  
  
items:  
  wand-axe:  
    enabled: true  
    name: "§r§fWand Axe\n§7§oBreak for first pos\n§7§oTouch for second pos"  
  blockinfo-stick:  
    enabled: false  
    name: "§r§fDebug Stick\n§7§oTouch block for info"  
...
```

## 💰 Credits

- Icon made by [Freepik](http://www.freepik.com/ "Freepik") from [www.flaticon.com](https://www.flaticon.com/ "Flaticon") is licensed by [CC 3.0 BY](http://creativecommons.org/licenses/by/3.0/ "Creative Commons BY 3.0")
- Design by kxj, discord kxj#5770

##  💡 License

```
Copyright 2018-2019 CzechPMDevs  
  
Licensed under the Apache License, Version 2.0 (the "License");  
you may not use this file except in compliance with the License.  
You may obtain a copy of the License at  
 
http://www.apache.org/licenses/LICENSE-2.0  
 
Unless required by applicable law or agreed to in writing, software  
distributed under the License is distributed on an "AS IS" BASIS,  
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.  
See the License for the specific language governing permissions and  
limitations under the License.
```

Full license [here](https://github.com/CzechPMDevs/BuilderTools/blob/master/LICENSE).
