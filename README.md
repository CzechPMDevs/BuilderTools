# BuilderTools

[![Poggit-CI](https://poggit.pmmp.io/ci.shield/CzechPMDevs/BuilderTools/BuilderTools)](https://poggit.pmmp.io/ci/CzechPMDevs/BuilderTools/BuilderTools)

- Plugin includes some WorldEdit commands


### BuilderTools works on:
- API 3.0.0-ALPHA7 +
- Minecraft: BedrockEdition v 1.2
- PocketMine, BlueLight

### Phar Download:

- Version 1.0.0:
    - Poggit: 
    - GitHub: https://github.com/CzechPMDevs/BuilderTools/releases/tag/1.0.0
    
### Advantages:

- Filling without lags.

### Plugin API.

- Get BuilderTools instance:

```
$builderTools = BuilderTools::getInstance();
```

- Get BuilderTools editors:

```
$filler = BuilderTools::getEditor("Filler");

if($filler instanceof Filler) {
    
}
```

- Use BuilderTools in plugin e.g. UHCRun cage

```
public function createCage(Position $position) {
    $printer = BuilderTools::getEditor("Printer");
    if($printer instanceof Printer) {
        $printer->draw($position, 6, Block::get(Block::GLASS), Printer::CUBE);
        $printer->draw($position, 5, Block::get(Block::AIR), Printer::CUBE);
    }
}

```

### Commands:

- //pos command:
    - Usage: //pos1 or //pos2
    - Description: Select pos1 / pos2
    - Permission: bt.cmd.pos1 (OP), bt.cmd.pos2 (OP)
    - Aliases: //1 and //2

- //fill command:
    - Usage: //fill <id1:dmg1,id2:dmg2,...>
    - Description: Fill selected area
    - Permission: bt.cmd.fill (OP)
    - Aliases: //set, //change
    
- //wand command:
    - Usage: //wand
    - Description: Turn on/off wand tool
    - Permission: bt.cmd.wand (OP)
    - How To Use:
        - 1: Execute command //wand to turn on wand tool
        - 2: Broke first block
        - 3: Click to the second block
        - 4: Use //set or //replace command to edit area
        - 5: Ececute command //wand to turn off wand tool

- //sphere command:
    - Usage: //sphere <id1:dmg1,id2:dmg2,...> <radius>
    - Description: Create sphere
    - Permission: bt.cmd.sphere (OP)
    
- //replace command:
    - Usage: //replace <BlocksToReplace: id1:dmg1,id2:dmg2,...> <Blocks: id1:dmg1,id2,dmg2>
    - Description: Replace selected block in selected area
    - Permission: bt.cmd.replace (OP)
    
- //help command:
    - Usage: .//help [page: 1-3]
    - Description: Displays all BuilderTools commands
    - Permission: bt.cmd.help (OP)
    - Aliases: //?

- //draw command:
    - Usage: //draw
    - Description: Draw with blocks
    - Permission: bt.cmd.draw (OP)
    - How To Use:
        - 1: Execute command //draw <brush: 1-6|on> [cube|sphere]:
            - the brush gives the value of the cube //sphere size.
            - you can choose between cubes or spheres
        - 2: Put your hand in the block with which you want to draw.
        - 3: click into the air to draw
        - 4: Execute command //draw off if you want to cancel draw

### Issues:

Plugin bugs upload [here](https://github.com/CzechPMDevs/BuilderTools/issues).
