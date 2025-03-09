<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>D&amp;D Yonder - Dungeons and Dragons API</title>

        <link href="/css/main.css" rel="stylesheet"/>
    </head>
    <body>
        <div class="content-container">
            <header>
                <h1>@include('components.logo-full')</h1>
                <h2>Easily generate D&amp;D characters for your campaigns.</h2>
            </header>
            <main>
                <section class="intro">
                    <h3 class="section-heading">Introduction</h3>
                    <p>I built this API because of my interest in Dungeons &amp; Dragons.
                        Using this API you can create and manage your characters, with the eventual goal to be able to play with those same characters.</p>
                    <p>The API is public, and free to use, and I will be expanding upon it to add new features, and improve upon the existing ones.</p>

                    <h3 class="section-heading">About the API</h3>
                    <ul class="about-api">
                        <li class="about-about">
                            <strong><a href="#section-about">About the API</a></strong>
                        </li>
                        <li class="about-getting-started">
                            <strong><a href="#section-getting-started">Getting Started</a></strong>
                        </li>
                        <li class="about-documentation">
                            <strong><a href="#section-documentation">API Documentation</a></strong>
                        </li>
                        <li class="about-troubleshooting">
                            <strong><a href="#section-troubleshooting">Troubleshooting</a></strong>
                        </li>
                    </ul>
                </section>
                <section class="section-about">
                    <h3 class="section-heading" id="section-about">About the API</h3>
                    <p>At the moment the API is still in its infancy, and I've only been developing it for a couple of months. So far, I've developed the following features that follow the original 5e ruleset:</p>
                    <ul>
                        <li>Character creation letting you select class, race, background, stats, languages, and spells.</li>
                        <li>Name suggestions using a Markov chain to create names applicable to different races from human to angelic.</li>
                        <li>Dice rolling for any of the standard 6 D&amp;D dice, allowing you to roll multiple different dice in one API call.</li>
                        <li>Random item generator, allowing you to pick the type and have a random item of that type returned. If you're lucky it might even be magic!</li>
                        <li>Encounter generator that can create a creature encounter applicable to the party and difficulty you specify.</li>
                    </ul>
                </section>

                <section class="section-getting-started">
                    <h3 class="section-heading" id="section-getting-started">Getting Started</h3>
                    <p><a href="/dnd-game-api.postman_collection.json">Get started using Postman</a></p>
                    <p>To get started just create a user on the register endpoint. You can use any email you want, it's just for login purposes, not for spam!</p>
                    <p>You can import this Postman collection and begin using it right away.</p>
                </section>

                <section class="section-documentation">
                    <h3 class="section-heading" id="section-documentation">Documentation</h3>

                    <details>
                        <summary>Users</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="post">POST</code> <code>/api/user/login</code>
                                <p>Log in</p>
                                <p>Params</p>
                                <ul>
                                    <li><code>email</code></li>
                                    <li><code>password</code></li>
                                </ul>
                                <p>Response</p>
<code class="block">{
    "token": &lt;JWT Token&gt;
}</code>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/user</code>
                                <p>Get user details</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Response</p>
<code class="block">{
    "user": {
        "id": 1,
        "name": "test",
        "email": "user@example.com",
        "email_verified_at": null,
        "created_at": "2025-01-21T10:54:51.000000Z",
        "updated_at": "2025-01-21T10:54:51.000000Z"
    }
}</code>
                            </li>
                            <li class="endpoint">
                                <code class="post">POST</code> <code>/api/user/register</code>
                                <p>Register user</p>
                                <p>Params</p>
                                <ul>
                                    <li><code>name</code></li>
                                    <li><code>email</code></li>
                                    <li><code>password</code></li>
                                    <li><code>password_confirmation</code></li>
                                </ul>
                                <p>Response</p>
<code class="block">{
    "user": {
        "id": 1,
        "name": "test",
        "email": "user@example.com",
        "created_at": "2025-01-21T10:54:51.000000Z",
        "updated_at": "2025-01-21T10:54:51.000000Z"
    },
    "token": "&lt;token&gt;"
}</code>
                            </li>
                            <li class="endpoint">
                                <code class="post">POST</code> <code>/api/user/logout</code>
                                <p>Log out</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Response</p>
<code class="block">{
    "message": "Successfully logged out"
}</code>
                            </li>
                        </ul>
                    </details>

                    <details>
                        <summary>Characters</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/characters</code>
                                <p>Get characters list</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/characters/classes</code>
                                <p>Get classes available for a character</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/characters/races</code>
                                <p>Get races available for a character</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/characters/backgrounds</code>
                                <p>Get backgrounds available for a character</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/characters/{guid}</code>
                                <p>Get classes available for a character</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>guid</code> the guid for a character returned by the characters list endpoint.</li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/characters/{guid}/spells/available</code>
                                <p>Get spells available for a character based on their class, race, and level</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>guid</code> the guid for a character returned by the characters list endpoint.</li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="post">POST</code> <code>/api/characters/</code>
                                <p>Create a new empty character</p>
                                <p>Post body</p>
                                <code class="block">{"name":"Some heroic name","level":1}</code>
                            </li>
                            <li class="endpoint">
                                <code class="patch">PATCH</code> <code>/api/characters/{guid}</code>
                                <p>Update a character with some partial creation details</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>guid</code> the guid for a character returned by the characters list endpoint.</li>
                                </ul>
                                <p>Post bodies</p>
<code class="block">
# Update class
{"updateType":"class",
    "charClassId":&lt;class-id&gt;}

# Update class and class path
{"updateType":"class",
    "charClassId":&lt;class-id&gt;,
    "classPathId":[&lt;class-path-id&gt;]}

# Update background and background characteristics
{"updateType":"background",
    "charBackgroundId":&lt;background-id&gt;,
    "characteristics":[&lt;characteristic-ids&gt;]}

# Update race
{"updateType":"race",
    "charRaceId":&lt;race-id&gt;}

# Update abilities (requires dice rolls for each ability first
# Abilities are in order 1-6, charisma, constitution, dexterity,
# intelligence, strength, and wisdom
{"updateType":"abilities",
    "abilityRolls": {
        {"abilityId": 1, "guid": &lt;dice-roll-guid&gt;},
        {"abilityId": 2, "guid": &lt;dice-roll-guid&gt;},
        {"abilityId": 3, "guid": &lt;dice-roll-guid&gt;},
        {"abilityId": 4, "guid": &lt;dice-roll-guid&gt;},
        {"abilityId": 5, "guid": &lt;dice-roll-guid&gt;},
        {"abilityId": 6, "guid": &lt;dice-roll-guid&gt;},
    }
}

# Update languages
{"updateType":"languages",
    "languages": [&lt;language-ids&gt;]}

# Update spells
{"updateType":"spells",
    "spells": [&lt;spell-ids&gt;]}
</code>
                            </li>
                        </ul>
                    </details>

                    <details>
                        <summary>Languages</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/languages</code>
                                <p>Get a list of all languages available for characters</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                            </li>
                        </ul>
                    </details>

                    <details>
                        <summary>Names</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/names</code>
                                <p>Get some random generic generated names</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Response</p>
<code class="block">{
    "style": "generic",
    "names": [
        "Span aladran axos",
        "Ayeniaspavadralia",
        "Ivore",
        "Iuzan axos",
        "Llica",
        "Niazator"
    ]
}</code>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/names/{nameType}</code>
                                <p>Get some random generic generated names</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>nameType</code> a racial type for the suggested names:
                                        <ul>
                                            <li>goblin</li>
                                            <li>orc</li>
                                            <li>ogre</li>
                                            <li>dwarf</li>
                                            <li>halfling</li>
                                            <li>gnome</li>
                                            <li>elf</li>
                                            <li>fey</li>
                                            <li>demon</li>
                                            <li>angel</li>
                                            <li>human</li>
                                            <li>tiefling</li>
                                        </ul>
                                    </li>
                                </ul>
                                <p>Response</p>
<code class="block">{
    "style": "generic",
    "names": [
        "Span aladran axos",
        "Ayeniaspavadralia",
        "Ivore",
        "Iuzan axos",
        "Llica",
        "Niazator"
    ]
}</code>
                            </li>
                        </ul>
                    </details>

                    <details>
                        <summary>Items</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/items/{itemType}</code>
                                <p>Get a list of all items by type</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>itemType</code> should be one of:
                                        <ul>
                                            <li>armor</li>
                                            <li>book</li>
                                            <li>clothing</li>
                                            <li>food</li>
                                            <li>other</li>
                                            <li>pack</li>
                                            <li>potion</li>
                                            <li>projectile</li>
                                            <li>weapon</li>
                                            <li>gemstone</li>
                                            <li>art object</li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/items/{itemType}/random</code>
                                <p>Get a random item by type (can generate new items for some types, like books, armor, and weapons.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>itemType</code> should be one of:
                                        <ul>
                                            <li>armor</li>
                                            <li>book</li>
                                            <li>clothing</li>
                                            <li>food</li>
                                            <li>other</li>
                                            <li>potion</li>
                                            <li>projectile</li>
                                            <li>weapon</li>
                                            <li>gemstone</li>
                                            <li>art object</li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </details>

                    <details>
                        <summary>Spells</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/spells/level/{level}</code>
                                <p>Get all spells by spell level.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>level</code> the spell level, <code>0</code> is for cantrips</li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/spells</code>
                                <p>Get all spells.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/spells/school/{school}</code>
                                <p>Get all spells by spell school.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>school</code> the spell school, one of:
                                        <ul>
                                            <li>abjuration</li>
                                            <li>conjuration</li>
                                            <li>divination</li>
                                            <li>enchantment</li>
                                            <li>evocation</li>
                                            <li>illusion</li>
                                            <li>necromancy</li>
                                            <li>transmutation</li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/spells/class/{classId}</code>
                                <p>Get all spells available for a specific class.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>classId</code> the character class, such as <code>12</code> for wizard</li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/spells/class/{classId}/level/{level}</code>
                                <p>Get all spells available for a specific class.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>classId</code> the character class, such as <code>12</code> for wizard</li>
                                    <li><code>level</code> the spell level, <code>0</code> is for cantrips</li>
                                </ul>
                            </li>
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/game/spells/school/{school}/level/{level}</code>
                                <p>Get all spells by spell school.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>school</code> the spell school, one of:
                                        <ul>
                                            <li>abjuration</li>
                                            <li>conjuration</li>
                                            <li>divination</li>
                                            <li>enchantment</li>
                                            <li>evocation</li>
                                            <li>illusion</li>
                                            <li>necromancy</li>
                                            <li>transmutation</li>
                                        </ul>
                                    </li>
                                    <li><code>level</code> the spell level, <code>0</code> is for cantrips</li>
                                </ul>
                            </li>
                        </ul>
                    </details>

                    <details>
                        <summary>Creatures</summary><ul class="endpoints">
                            <li class="endpoint">
                                <code class="get">GET</code> <code>/api/creatures/{creatureType}</code>
                                <p>Get all creatures by type.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Params</p>
                                <ul>
                                    <li><code>creatureType</code> the type of creature, one of:
                                        <ul>
                                            <li>aberration</li>
                                            <li>beast</li>
                                            <li>celestial</li>
                                            <li>construct</li>
                                            <li>demon</li>
                                            <li>devil</li>
                                            <li>dragon</li>
                                            <li>elemental</li>
                                            <li>fey</li>
                                            <li>giant</li>
                                            <li>humanoid</li>
                                            <li>monstrosity</li>
                                            <li>ooze</li>
                                            <li>plant</li>
                                            <li>undead</li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                    </details>

                    <details>
                        <summary>Dice</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="post">POST</code> <code>/api/game/dice</code>
                                <p>Roll one or more dice of different types. Rolls are stored in the database against a guid which you can use later.</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Post body</p>
<code class="block">{
    "dice":{
        "d6":4,
        "d20":2
    }
}</code>
                                <p>You can specify any of the standard D&amp;D dice:</p>
                                <ul>
                                    <li><code>d4</code></li>
                                    <li><code>d6</code></li>
                                    <li><code>d8</code></li>
                                    <li><code>d10</code></li>
                                    <li><code>d12</code></li>
                                    <li><code>d20</code></li>
                                </ul>
                                <p>Response</p>
<code class="block">{
    "rolls": {
        "d6": [
            1,
            6,
            4,
            3
        ],
        "d20": [
            17,
            1
        ]
    },
    "guid": "&lt;roll-guid&gt;"
}</code>
                            </li>
                        </ul>
                    </details>

                    <details>
                        <summary>Encounters</summary>
                        <ul class="endpoints">
                            <li class="endpoint">
                                <code class="post">POST</code> <code>/api/encounters</code>
                                <p>Creates an encounter appropriate for the party of characters and difficulty level</p>
                                <p>Header Params</p>
                                <ul>
                                    <li><code>Authorization: Bearer &lt;token&gt;</code></li>
                                </ul>
                                <p>Post body</p>
<code class="block">{
    "characters": [&lt;character-guids&gt;],
    "difficulty": &lt;1-4&gt;,
    "environment": &lt;environment&gt;
}</code>
                                <p>Post body params:</p>
                                <ul>
                                    <li><code>difficulty</code> 1 = easy, 2 = medium, 3 = hard, 4 = deadly</li>
                                    <li><code>environment</code> one of the following:
                                        <ul>
                                            <li>arctic</li>
                                            <li>coast</li>
                                            <li>desert</li>
                                            <li>forest</li>
                                            <li>grassland</li>
                                            <li>hill</li>
                                            <li>mountain</li>
                                            <li>swamp</li>
                                            <li>underdark</li>
                                            <li>underwater</li>
                                            <li>urban</li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </details>
                </section>

                <section class="section-troubleshooting">
                    <h3 class="section-heading" id="section-troubleshooting">Troubleshooting</h3>

                    <p>If you run into trouble, please <a href="mailto:ash@ashleysheridan.co.uk">contact me by email</a> and I will
                        endeavour to respond as soon as I'm able. Over time I will populate this with recurring troubleshooting
                        issues (those that can't be fixed).</p>
                </section>
            </main>
            <footer>

            </footer>
        </div>
    </body>
</html>
