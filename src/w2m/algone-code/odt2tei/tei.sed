#!/bin/sed -E -f
# Chained substitutions on a TEI generated from WordProcessor mess
# should work as a sed script in unicode (on one line, no dotall option)
# those regexp will be interpreted in PHP, Java, may be Python
# default MacOSX sed is not perl compatible, there is no support for backreference in search “\1”, and assertions (?…)
# frederic.glorieux@algone.net

# SECTIONS

s@<\?div ?\?>@<div>@g
s@<\?div /\?>@</div>@g


# GROUPING

# xsl produce series like <entry><form>LEMMA</form><!-- DEL --></entry><entry><!-- /DEL --><def>DEFINITION</def></entry>
s@(</entry>\s*)(<entry>\s*<form>)@\1<?o?>\2@g 
s@(</entryFree>\s*)(<entryFree>\s*<form>)@\1<?o?>\2@g 
s@(</castItem>\s*)(<castItem>\s*<role>)@\1<?o?>\2@g
s@</(address|argument|castItem|dialogue|dl|entry|entryFree|epigraph|figure|index|lg|quote|set|ul)>\s*<\1( [^>]*)?>@@g
# empty can be used as separator
s@<(argument|castItem|dialogue|dl|entry|entryFree|eg|figure|index|lg|quote|set|ul)/>@\n@g
s@<\?o\?>@@g
s@</eg>\s*<eg( [^>]*)?>@@g
s@<lb type="lg"/>([^\n]+)@<l>\1</l>@g
s@(<term type="[^"]+">)[^<:]+: *@\1@g # suppress term key

# EMPTY AND SPACES

# suppress inline empty tags before normalisation
s@<(bg_[^> ]+|color_[^> ]+|mark_[^> ]+|font_[^> ]+|b|code|em|emph|hi|i|num|phr|ref|s|sc|strong|sub|surname|sup|strong|title|u)( [^>]+)?/>@@g
# <seg rend="lang-el">ρυθμιζειν</seg><seg rend="lang-el"> </seg><seg rend="lang-el">τας</seg>
# <hi>The Holly</hi> <hi>Bible</hi> => <hi>The Holly Bible</hi>
# <title rend="solid">The </title><title rend="solid"><emph>Quichotte</emph></title>
# do color first
s@</(bg_[^> ]+|color_[^> ]+|mark_[^> ]+|font_[^> ]+)>([  ,.:’-]*)<\1( [^>]+)?>@\2@g
# </pb>.<pb> => ., \s ?
s@</(b|bibl|code|em|emph|hi|i|num|pb|phr|s|sc|strong|surname|sub|sup|title|u)>([  ,.:’-]*)<\1( [^>]+)?>@\2@g
# exit line break from inline tags
s@(<lb/>\s*)</(b|em|emph|hi|i|name|num|phr|s|sc|strong|sub|sup|title|u)>@</\2>\1@g
s@(<note[^>]*>) +@$1@g
s@</quote>( *<pb>[^<]*</pb> *)<quote[^>]*>@$1@g # inline quotes, insert page breaks (after upper)
s@</quote>( *<note[^>]*>.+?</note> *)<quote[^>]*>@$1@g # inline quotes insert notes, ungreedy!

# PUNCTUATION

# suppress inline tags with spaces <mark_ffffcc> </mark_ffffcc> =>  (be careful to empty cells, or paras with unbreakable space)
# will not work in sed
# <sup>’</sup>
s@<(bg_[^> ]+|color_[^> ]+|mark_[^> ]+|font_[^> ]+|b|bibl|code|em|emph|hi|i|surname|num|phr|s|sc|strong|sub|sup|strong|title|u)( [^>]+)?>([ : \‑\.\(\),\]\[’“”«»]*)</\1>@\3@g
# <num>. ii</num> => . <num>ii</num>, <i>— L’Ordene de Chevalerie</i>
s@<(b|em|emph|hi|i|num|s|sc|surname|phr|title|u)( [^>]+)?>([ :\,;!?\-\. \)\]*•—–]+)@\3<\1\2>@g
# exit some punctuation from inline tags !! &amp;
s@\s*( ;)</(b|emph|em|hi|i|phr|strong|sub|sup|title|u)>@</\2>\1@g
s@([  \-\,:!\?]+)</(b|bibl|em|emph|hi|i|phr|strong|sub|sup|title|u)>@</\2>\1@g

# LINKS

# s@% *([0-9]+)[ \.]*%@<anchor n="%\1%"/>@g # NON, utilisé dans les URI
s@\$ *([0-9]+)[ \.]*\$@<anchor n="\$\1\$"/>@g
s@</ref><ref[^>]+>@@g
# <ref target="@n003">3</ref><anchor xml:id="t003"/> => <ref target="@n003" xml:id="t003">3</ref>
# buggy
#s@<([^ ]+)( [^>]*)?>([^<]*)</\1>([ ., :;?\[\]\(\)]*)<anchor xml:id="([^"]+)"/>@<\1\2 xml:id="\5">\3</\1>\4@g
# (<ref target="@fn2">2</ref>) => <ref target="@fn2">(2)</ref>
s@\((<ref [^>]+>)([^\[\]\(\)<]+)(</ref>)\)@\1(\2)\3@g
s@\[(<ref [^>]+>)([^\[\]\(\)<]+)(</ref>)\]@\1[\2]\3@g



# ABBR

# <hi>f.</hi> <hi>v.</hi> => <abbr>f.</abbr> <abbr>v.</abbr>
s@<hi>(cf\.?|etc\.?|éd\.?|s[cv]\.|[a-z]\.)</hi>@<abbr>\1</abbr>@g
# <abbr>cf</abbr>. => <abbr>cf.</abbr>
s@</abbr>\.@.</abbr>@g


# SMALL-CAPS

# eux-m</surname>ê<surname>mes
s@</sc>([  ,.:âäæçéèêëîïôöœüû]*)<sc( [^>]+)?>@\1@g
# small caps (xii<sup>e</sup>–xiii<sup>e</sup>)
s@([\(\s –\-])([ivx]+<sup>e</sup>)@\1<num>\2</num>@g
# <surname>Hugo</surname> S. <surname>Vict.</surname> => <surname>Hugo S. Victor</surname>, <surname>Ioh. </surname><surname>Scot.</surname> => <surname>Ioh. Scot.</surname>
s@</surname>([ A-ZÉÈÇ\.]*)<surname>@\1@g
s@</sc>([ A-ZÉÈÇ\.]*)<sc>@\1@g
# G<surname>aufredus</surname> => <surname>Gaufredus</surname>
s@([A-ZÉÈÇ][A-ZÉÈÇ\.  ]*)<surname>@<surname>\1@g
s@([A-ZÉÈÇ][A-ZÉÈÇ\.  ]*)<sc>@<sc>\1@g
# <surname> [Louis</surname>] => [<surname>Louis</surname>]
s@<(surname|sc)>([  \{\]\[\(\)0-9\.\-—–]+)@\2<\1>@g
# <surname>Voltaire),</surname> => <surname>Voltaire</surname>),
s@([  \}\]\):\‑,0-9]+)</(surname|sc)>@</\2>\1@g
# <surname>Pedro, Ibanez, Juan</surname> => <surname>Pedro</surname>, <surname>Ibanez, Juan</surname>
s@<surname>([^<]*)( *[\(\)][ \.]*)([^<]*)</surname>@<surname>\1</surname>\2<surname>\3</surname>@g
# <surname>Descartes R</surname>ené => <surname>Descartes</surname> René
s@( +[A-Z])</(surname|sc)>@</\2>\1@g
# !! <surname>Lex</surname> => <num>Lex</num>
# <num>VII. 6</num> => <num>VII.</num> 6
s@([  \.0-9]+)</num>@</num>\1@g
s@<num>(\.)@\1<num>@g
# parenthesis and number
s@([\)\],]*)</num>@</num>\1@g
# <num>2</num><sup>2</sup>=4 => <num>2<sup>2</sup></num> = 4
s@</(num|sc|surname)>( *<sup>[^<]+</sup>)@\2</\1>@g
# <num>I</num>º => <num>Iº</num>
s@</(num)>( *[⁰¹²³⁴⁵⁶⁷⁸⁹ªᵇᶜᵈᵉᶠᵍʰⁱʲᵏˡᵐⁿºᵖʳˢᵗᵘᵛʷˣʸᶻ₀₁₂₃₄₅₆₇₈₉ₐₑᵢₒᵤ]+\.?)@\2</\1>@g



# INDEX

# error <mark_000000>blah</mark_000000> blah blah <mark_000000>blah</mark_000000><term rend="index" key="BLAH"/>
# ====> <term key="BLAH">blah</mark_000000> blah blah <mark_000000>blah</term>
s@</(bg_[^> ]+|color_[^> ]+|font_[^> ]+|mark_[^> ]+|b|em|emph|hi|i|phr|title|u)>(\s*)(<term rend="index"[^>]+/>)@\3</\1>\2@g
s@(<term rend="index"[^>]+/>)(\s*)<(bg_[^> ]+|color_[^> ]+|font_[^> ]+|mark_[^> ]+|b|em|emph|hi|i|phr|title|u)>@\2<\3>\1@g

# trim some spaces and non significative punctuation
# s@<(item|bibl)( [^>]+)?>[\s ]*[-*•—–][\s ]*@<\1\2>@g
s@<(cell|item|head|p)( [^>]+)?> +@<\1\2>@g
s@<space/>\s*</(cell|head|item|l|p)>@</\1>@g
s@<space>[  ]*</space>\s*</(cell|head|item|l|p)>@</\1>@g
s@ +</(cell|head|item|l|p)>@</\1>@g

# FRENCH PUNCTUATION (after spacing norm)

# Attention à ne pas bousculer le XML, ex: espaces autour de '.', ':' 
s@([^<][,!?!»\)\}\]])([^  ><…,\.\)\]\}])@\1 \2@g
s@([^  <\[(?!])([!?»][^>])@\1 \2@g

s@ ([:;!?»\)\}\]])@ \1@g
s@([«])([^  ])@\1 \2@g
s@([«\(\[\{]) @\1 @g
s@([,;.?!]) ([—–]) @\1 \2 @g
s@(c|d|j|l|m|n|qu|s|t)'@\1’@g
s@(['’])[  ]*»[  ]+@\1 « @g
