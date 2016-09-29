using System.Collections.Generic;

namespace PlaneSculptorsDiscordBot
{
    public class JsonCard
    {
        public string Layout { get; set; }
        public string Name { get; set; }
        public string ManaCost { get; set; }
        public string Type { get; set; }
        public List<string> Types { get; set; }
        public List<string> Subtypes { get; set; }
        public string Text { get; set; }
        public string Flavor { get; set; }
        public string Power { get; set; }
        public string Toughness { get; set; }
        public string Loyalty { get; set; }
        public string ImageName { get; set; }
        public List<string> Names { get; set; }
        public string Rarity { get; set; }
        public int MultiverseId { get; set; }

        public int TextLength { get; set; }

        public bool Processed { get; set; }

        public JsonCard RelatedCard { get; set; }

        public override string ToString()
        {
            var str = $"**{this.Name}**    {this.ManaCost}\n{this.Type}    \n";
            if (!string.IsNullOrWhiteSpace(this.Text))
            {
                str += this.Text + "\n";
            }

            if (!string.IsNullOrWhiteSpace(this.Flavor))
            {
                str += this.Flavor + "\n";
            }

            if (!string.IsNullOrWhiteSpace(this.Power) || !string.IsNullOrWhiteSpace(this.Toughness))
            {
                str += this.Power + "/" + this.Toughness + "\n";
            }

            str = str.Replace("{W}", "[W]");
            str = str.Replace("{U}", "[U]");
            str = str.Replace("{B}", "[B]");
            str = str.Replace("{R}", "[R]");
            str = str.Replace("{G}", "[G]");
            str = str.Replace("{0}", "[0]");
            str = str.Replace("{1}", "[1]");
            str = str.Replace("{2}", "[2]");
            str = str.Replace("{3}", "[3]");
            str = str.Replace("{4}", "[4]");
            str = str.Replace("{5}", "[5]");
            str = str.Replace("{6}", "[6]");
            str = str.Replace("{7}", "[7]");
            str = str.Replace("{8}", "[8]");
            str = str.Replace("{9}", "[9]");
            str = str.Replace("{10}", "[10]");
            str = str.Replace("{11}", "[11]");
            str = str.Replace("{12}", "[12]");
            str = str.Replace("{13}", "[13]");
            str = str.Replace("{14}", "[14]");
            str = str.Replace("{15}", "[15]");
            str = str.Replace("{16}", "[16]");
            str = str.Replace("{17}", "[17]");
            str = str.Replace("{18}", "[18]");
            str = str.Replace("{19}", "[19]");
            str = str.Replace("{20}", "[20]");
            str = str.Replace("{T}", "[T]");
            str = str.Replace("{E}", "[E]");
            str = str.Replace("{S}", "[S]");
            str = str.Replace("{W/P}", "[PW]");
            str = str.Replace("{U/P}", "[PU]");
            str = str.Replace("{B/P}", "[PB]");
            str = str.Replace("{R/P}", "[PR]");
            str = str.Replace("{G/P}", "[PG]");
            str = str.Replace("{W/2}", "[2W]");
            str = str.Replace("{U/2}", "[2U]");
            str = str.Replace("{B/2}", "[2B]");
            str = str.Replace("{R/2}", "[2R]");
            str = str.Replace("{G/2}", "[2G]");
            str = str.Replace("{W/U}", "[WU]");
            str = str.Replace("{U/B}", "[UB]");
            str = str.Replace("{B/R}", "[BR]");
            str = str.Replace("{R/G}", "[RG]");
            str = str.Replace("{W/G}", "[WG]");
            str = str.Replace("{W/B}", "[WB]");
            str = str.Replace("{B/G}", "[BG]");
            str = str.Replace("{G/U}", "[GU]");
            str = str.Replace("{U/R}", "[UR]");
            str = str.Replace("{R/W}", "[RW]");
            str = str.Replace("{Q}", "[UT]");

            if (this.RelatedCard != null)
            {
                str += $"//\n{this.RelatedCard}";
            }

            return str;
        }
    }
}