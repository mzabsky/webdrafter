using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;
using Discord;
using Newtonsoft.Json;

namespace PlaneSculptorsDiscordBot
{
    class Program
    {
        private static Dictionary<string, string> officialCardIndex;

        private static Dictionary<string, string> PepareOfficialCardData()
        {
            var allCards = JsonConvert.DeserializeObject<Dictionary<string, JsonCard>>(File.ReadAllText("AllCards.json", Encoding.UTF8)).Values.ToList();

            var additionalCards = new List<JsonCard>();

            // Split, double faced and flip cards are represented as a separate left and right half in the source data
            foreach (var leftHalf in allCards.Where(p => p.Layout == "split" && p.Names.FirstOrDefault() == p.Name))
            {
                var rightHalfName = leftHalf.Names.LastOrDefault();
                var rightHalf = allCards.Single(p => p.Name == rightHalfName);

                leftHalf.RelatedCard = rightHalf;
            }

            foreach (var leftHalf in allCards.Where(p => p.Layout == "double-faced" && p.Names.FirstOrDefault() == p.Name))
            {
                var rightHalfName = leftHalf.Names.LastOrDefault();
                var rightHalf = allCards.Single(p => p.Name == rightHalfName);

                leftHalf.RelatedCard = rightHalf;
            }

            foreach (var leftHalf in allCards.Where(p => p.Layout == "flip" && p.Names.FirstOrDefault() == p.Name))
            {
                var rightHalfName = leftHalf.Names.LastOrDefault();
                var rightHalf = allCards.Single(p => p.Name == rightHalfName);

                leftHalf.RelatedCard = rightHalf;
            }

            allCards.AddRange(additionalCards);

            var index = allCards.ToDictionary(p => p.Name.ToLowerInvariant(), card => card.ToString());

            return index;
        }

        static void Main(string[] args)
        {
            officialCardIndex = PepareOfficialCardData();

            var client = new DiscordClient(x => {
                x.AppName = "PlaneSculptor";
                x.AppUrl = "http://www.planesculptors.net";
                x.MessageCacheSize = 500;
                x.UsePermissionsCache = true;
                x.EnablePreUpdateEvents = true;
                x.LogLevel = LogSeverity.Debug;
                x.LogHandler = (sender, eventArgs) =>
                {
                    Console.WriteLine("Log " + DateTime.Now + ": " + eventArgs.Message);
                    File.AppendAllText("log.txt", DateTime.Now + ": " + eventArgs.Message + Environment.NewLine);
                };
            });

            client.MessageReceived += async (sender, eventArgs) => await ClientOnMessageReceived(eventArgs);

            client.ExecuteAndWait(async () => {
                try
                {
                    await client.Connect("MjIzNDAwNzcyMjI0Njc5OTM3.CriMzQ._gAClVObFo91C_zoUNZRweJBrh8", TokenType.Bot);
                    client.SetGame("PlaneSculptors.net", GameType.Default, "https://www.planesculptors.net");
                    client.SetStatus(UserStatus.Online);
                }
                catch (Exception e)
                {
                    Console.WriteLine("Crashed with exception, restarting.");                                  
                    Console.WriteLine(e);
                }
            });
        }

        private static async Task ClientOnMessageReceived(MessageEventArgs e)
        {
            try
            {
                // Parse the message
                var regex = @"\[\[((?<set>[^:]+|[0-9]+|[0-9]+):((?<version>[a-z][a-z0-9-]+|[0-9]+|[0-9]+):)?)?(?<card>[^:]+?|[0-9]+)?\]\]";
                var match = Regex.Match(e.Message.Text, regex);

                if (!match.Success) return;

                var context = !e.Channel.IsPrivate ? e.Channel.Name : "";
                
                var url = $@"http://www.planesculptors.net/autocard?context={context}&contextVersion=&set={match.Groups["set"]}&setVersion={match.Groups["version"]}&card={match.Groups["card"]}&bot";


                Console.WriteLine($"#{e.Channel} {e.User}: {e.Message} -> {url}");

                // Download card data from PlaneSculptors.net
                HttpWebRequest request = (HttpWebRequest)WebRequest.Create(url);
                var response = request.GetResponse();
                var responseStream = response.GetResponseStream();
                var responseContentStream = new StreamReader(responseStream, Encoding.UTF8);
                var responseContent = await responseContentStream.ReadToEndAsync();

                // If a card was not found, try looking in the official card database
                if (responseContent.Length > 2000 || responseContent == "Card not found." || responseContent == "Set not found.")
                {
                    bool officialFound = false;
                    if (string.IsNullOrWhiteSpace(match.Groups["set"].ToString()) || match.Groups["set"].ToString() == "*" || string.Compare(match.Groups["set"].ToString(), "mtg", StringComparison.OrdinalIgnoreCase) == 0)
                    {
                        var name = match.Groups["card"].ToString();
                        var matchingCards =
                            officialCardIndex.Keys.Where(k => k.Contains(name.ToLowerInvariant())).Select(p => officialCardIndex[p]).Take(4).ToList();

                        if (matchingCards.Any())
                        {
                            officialFound = true;

                            var completeString = string.Join("\n\n", matchingCards.Take(3));
                            if (matchingCards.Count == 4) completeString += "**More than three matching cards were found. Please narrow down your search.**";

                            await e.Channel.SendMessage(completeString);
                        }
                    }

                    if (!officialFound)
                    {
                        await e.Channel.SendMessage(responseContent);
                    }
                }
                else
                {
                    await e.Channel.SendMessage(responseContent);
                }
                
            }
            catch (Exception ex)
            {
                Console.WriteLine(ex);
            }
        }
    }
}
