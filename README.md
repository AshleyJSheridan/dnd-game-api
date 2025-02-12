# DnD Yonder API

An API for DnD using the pre-2024 5e rules.

## Installation

* Standard Laravel project
* Database currently not using migrations - use latest SQL dump in `database` directory

## API Endpoints

### Characters

_Endpoints here currently use a hard-coded user id of 1. This will change once auth endpoints are added._

#### `GET /characters`

Get a list of all characters for the current user.

#### `POST /characters`

Create a new character. The returned `guid` will be used to interact with this character later.

#### `GET /characters/classes`

Gets a list of all available classes for a player character.

#### `GET /characters/backgrounds`

Gets a list of all available backgrounds for a player character.

#### `GET /characters/races`

Gets a list of all available races for a player character.

#### `GET /characters/{guid}`

Gets a specific character.

#### `PATCH /characters/{guid}`

Updates details for a specific character. The body of the request should contain details required for that type of update:

* `{"updateType": "class", "charClassId": class_id}` - where `class_id` is one of those returned from the `GET /characters/classes` endpoint.
* `{"updateType": "background", "charBackgroundId": `background_id`, "characteristics": []}` - where `background_id` is one of those returned from the `GET /characters/backgrounds` endpoint, and the `characteristics` array is a list of selected characteristics appropriate to that background.
* `{"updateType": "race", "charRaceId": race_id}` - where `race_id` is one of those returned from the `GET /characters/races` endpoint.
* `{"updateType": "abilities", "abilityRolls": [{"abilityId": [1-6], "guid": dice_roll_guid}]}` - where all 6 abilities (1-6) have a corresponding `guid` that matches a previous dice roll request. Each guid must be unique, and should be a 4d6 roll as per character creation rules.
* `{"updateType": "languages", "languages": []}` - where `languages` is an array of language ids. If more are given than a character has available, the list will be truncated.

### Languages

#### `GET /game/languages`

Gets a list of all languages available for a character to know.

### Names

#### `GET /characters/name`

Gets a list of randomly generated names for a character to use.

#### `GET /characters/name/{nameType}`

Gets a list of randomly generated names for a character to use based on one of the following:

* angel
* demon
* dwarf
* elf
* fey
* generic
* gnome
* goblin
* halfling
* ogre
* orc

### Items

#### `GET /game/items`

Gets a list of all items currently in the DB.

#### `GET /game/items/{itemType}`

Gets a list of all items of a specific type currently in the DB. The type can be one of:

* armor
* book
* clothing
* food
* other
* pack
* potion
* projectile
* weapon

#### `GET /game/items/{itemType}/random`

Gets a random item of the specified type. This may also return a randomly generated item of the specified type, which is added to the DB. The type should be one of:

* armor - this can accept a query param called `proficiency` that can be one of the following values used to limit the type of returned item:
  * heavy
  * medium
  * light
  * shield
* book
* clothing
* food
* other
* potion
* projectile
* weapon - this can accept a query param called `proficiency` that can be one of the following values used to limit the type of returned item:
  * melee (simple)
  * melee (martial)
  * ranged (simple)
  * ranged (martial)

### Spells

#### `GET /game/spells`

Gets a list of all spells.

#### `GET /game/spells/level/{level}`

Gets a list of all spells for a specific level 0-9, where 0 is cantrip.

#### `GET /game/spells/school/{school}`

Gets a list of all spells for a specific school, which should be one of:

* abjuration
* conjuration
* divination
* enchantment
* evocation
* illusion
* necromancy
* transmutation

#### `GET /game/spells/school/{school}/level/{level}`

Gets a list of all spells for a specific school and level. Level should be 0-9 (where 0 is cantrip), and school should be one of:

* abjuration
* conjuration
* divination
* enchantment
* evocation
* illusion
* necromancy
* transmutation

#### `GET /game/spells/class/{classId}`

Gets all spells for a specific class, using one of the class id values returned from `GET /characters/classes`

#### `GET /game/spells/class/{classId}/level/{level}`

Gets all spells of the specified level or below for a specific class, using one of the class id values returned from `GET /characters/classes`.

### Dice

#### `POST /game/dice`

Creates rolls of the dice specified in the body of the request. A sample request for 4d6 and 2d20 would look like this:

```
{"dice": {"d6": 4, "d20": 2}}
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
