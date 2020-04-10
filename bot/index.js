const Discord = require('discord.js')
const axios = require('axios')

const client = new Discord.Client()
client.on('ready', () => {
  console.log(`Logged in as ${client.user.tag}!`)
})

var regex = new RegExp('\\[\\[(([^:]+|[0-9]+|[0-9]+):(([a-z][a-z0-9-]+|[0-9]+|[0-9]+):)?)?([^:]+?|[0-9]+)?\\]\\]', 'g')
const symbolMapping = {
  '[W]': '<:W_:233094502640910336>',
  '[U]': '<:U_:233094523012644864>',
  '[B]': '<:B_:233094539970084864>',
  '[R]': '<:R_:233094555346403328>',
  '[G]': '<:G_:233094570705944576>',
  '[0]': '<:0_:233087928111333376>',
  '[1]': '<:1_:233087941465997312>',
  '[2]': '<:2_:233087976723185665>',
  '[3]': '<:3_:233087977377628160>',
  '[4]': '<:4_:233087977855778826>',
  '[5]': '<:5_:233087978300243968>',
  '[6]': '<:6_:233087978342187008>',
  '[7]': '<:7_:233087978350706688>',
  '[8]': '<:8_:233087978409426944>',
  '[9]': '<:9_:233087978413621251>',
  '[10]': '<:10:233087978417815552>',
  '[11]': '<:11:233095414390194176>',
  '[12]': '<:12:233095464222851074>',
  '[13]': '<:13:233095464428240896>',
  '[14]': '<:14:233095464533098496>',
  '[15]': '<:15:233095536889036803>',
  '[X]': '<:X_:233088003591897098>',
  '[T]': '<:T_:233088054674456577>',
  '[WU]': '<:WU:233082066831409162>',
  '[WB]': '<:WB:233095081316319244>',
  '[UB]': '<:UB:233082236444868618>',
  '[UR]': '<:UR:233095125914484737>',
  '[BR]': '<:BR:233082275330392064>',
  '[BG]': '<:BG:233095174572605440>',
  '[RG]': '<:RG:233082296717017099>',
  '[RW]': '<:RW:233095204649828352>',
  '[GW]': '<:GW:233082340417601537>',
  '[GU]': '<:GU:233095244332138497>',
  '[E]': '<:E_:233094452661583872>',
  '[PW]': '<:PW:233095689058517002>',
  '[PU]': '<:PU:233095720033452033>',
  '[PB]': '<:PB:233095745371111424>',
  '[PR]': '<:PR:233095765919006720>',
  '[PG]': '<:PG:233095788140560385>',
  '[C]': '<:C_:233094585830735872>',
  '[2W]': '<:2W:234047019369496578>',
  '[2U]': '<:2U:234047018601807873>',
  '[2B]': '<:2B:234047017595174922>',
  '[2R]': '<:2R:234047018043965442>',
  '[2G]': '<:2G:234047017981050881>'
}
client.on('message', msg => {
  var text = msg.content

  // Custom Standard channel exception
  if(msg.channel.guild && msg.channel.guild.id == "481200347189084170") {
    if(!msg.isMentioned(client.user)) {
      return;
    }      
    text = "[[" + text.replace(/<@[0-9]+>/g, '').trim() + "]]";
  }

  if (text.includes('$msem') || text.includes('$myriad')) {
    return;
  }

  var match = regex.exec(text)
  if (!match) return  

  var set = match[2]
  var setVersion = match[4]
  var card = match[5].replace(/'/g, '_').replace(/’/g, '_').replace(/\*/g, '%')

  var url = `http://www.planesculptors.net/autocard?context=${msg.channel.name}contextVersion=&set=${set || ''}&setVersion=${setVersion || ''}&card=${card}&bot`

  axios.get(url)
  .then(response => {
    var responseText = response.data
    responseText = responseText.replace(/\[[A-Z0-9]{1,2}\]/g, r => msg.channel.guild.name === 'Custom Magic' ? (symbolMapping[r] || r) : r)
    msg.reply('\n' + responseText)
  })
  .catch(error => {
    msg.reply(`Something went wrong while processing the request:` + error)
  })
})
.on('error', e => console.log(e))

client.login('MjIzNDAwNzcyMjI0Njc5OTM3.DcJlqg.g4Fvoq93fFZSlB1fGKx39itj5cg')
