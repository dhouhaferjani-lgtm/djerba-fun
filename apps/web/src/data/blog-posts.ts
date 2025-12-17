export interface BlogPost {
  slug: string;
  title: string;
  excerpt: string;
  content: string;
  coverImage: string;
  date: string;
  author: {
    name: string;
    avatar: string;
    bio: string;
  };
  category: string;
  tags: string[];
  readTime?: string;
}

const blogPosts: BlogPost[] = [
  {
    slug: 'hidden-gems-tunisia',
    title: 'Hidden Gems of Tunisia: 10 Places Most Tourists Never See',
    excerpt:
      'Venture beyond the typical tourist trail and discover the secret wonders of Tunisia that will take your breath away.',
    coverImage: 'http://localhost:9002/go-adventure/featured/djerba-island.jpg',
    date: 'December 10, 2024',
    author: {
      name: 'Sarah Johnson',
      avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200',
      bio: 'Adventure travel writer and photographer with 10+ years exploring North Africa. Passionate about sustainable tourism and cultural preservation.',
    },
    category: 'Travel Guide',
    tags: ['Tunisia', 'Hidden Gems', 'Off the Beaten Path', 'Adventure'],
    readTime: '8 min read',
    content: `Tunisia is a land of contrasts, where golden Sahara dunes meet Mediterranean beaches, and ancient Roman ruins stand alongside vibrant souks. While places like Carthage and Sidi Bou Said attract thousands of visitors, some of Tunisia's most spectacular sites remain beautifully undiscovered.

## 1. Ksar Ouled Soltane

Deep in southern Tunisia lies this stunning fortified granary, a masterpiece of Berber architecture. Unlike the more famous Ksar Hadada, Ouled Soltane remains relatively untouched by mass tourism. The four-story structure, with its honeycomb of storage rooms called "ghorfas," offers photographers dream-worthy shots at every turn.

The best time to visit is early morning when the golden light illuminates the earthen walls, and you might have the entire place to yourself. Local guides can share stories of how these structures protected grain stores during harsh times.

## 2. Chebika Oasis

This mountain oasis is a hidden paradise in the Atlas Mountains. A crystal-clear stream cascades through palm groves and rock formations, creating natural pools perfect for a refreshing dip after a desert hike. The trek to reach Chebika involves navigating narrow canyon passages, adding an element of adventure to the experience.

## 3. Dougga Roman Ruins

While not entirely unknown, Dougga receives a fraction of the visitors that Carthage does, yet it's arguably more impressive. This UNESCO World Heritage site is one of the best-preserved Roman towns in North Africa. The Capitol, temples, and theater sit majestically on a hillside, offering sweeping views of the Tunisian countryside.

## 4. Ain Draham

Tucked away in the Kroumirie Mountains, this alpine-style town feels worlds apart from the desert landscapes typically associated with Tunisia. Cork oak forests, cool mountain air, and charming red-roofed houses make this a perfect summer escape. It's popular with locals but rarely appears on international tourist itineraries.

## 5. Kerkennah Islands

These low-lying islands off the coast of Sfax offer a glimpse into traditional Tunisian fishing life. With no high-rise hotels or beach clubs, the Kerkennah Islands provide an authentic, slower-paced alternative to Djerba. Rent a bicycle and explore quiet beaches, watch traditional octopus fishing, and enjoy the freshest seafood you'll ever taste.

## Planning Your Visit

The best time to explore these hidden gems is during spring (March-May) or fall (September-November) when temperatures are moderate. Consider hiring a local guide who can provide cultural context and access to areas that aren't well-marked for tourists.

Remember to respect local customs, dress modestly when visiting traditional villages, and always ask permission before photographing people. These places remain special partly because they've been treated with care by the few travelers who visit them.

## Getting Around

Renting a car provides the most flexibility for reaching these off-the-beaten-path destinations. However, many can also be visited as part of organized tours from major cities. We recommend staying in locally-owned guesthouses rather than international hotels to support the communities you're visiting and gain deeper cultural insights.`,
  },
  {
    slug: 'sustainable-travel-tunisia',
    title: 'The Complete Guide to Sustainable Travel in Tunisia',
    excerpt:
      'Learn how to explore Tunisia responsibly while supporting local communities and protecting the environment for future generations.',
    coverImage: 'http://localhost:9002/go-adventure/featured/sahara-desert.jpg',
    date: 'December 5, 2024',
    author: {
      name: 'Ahmed Mansouri',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200',
      bio: 'Sustainability advocate and Tunisia native. Founder of several eco-tourism initiatives across North Africa.',
    },
    category: 'Eco Travel',
    tags: ['Sustainability', 'Eco Tourism', 'Responsible Travel', 'Environment'],
    readTime: '10 min read',
    content: `As tourism grows in Tunisia, it's more important than ever to travel in ways that preserve this beautiful country's natural and cultural heritage. Sustainable travel isn't about sacrifice—it's about making thoughtful choices that enhance your experience while benefiting local communities and the environment.

## Understanding the Impact

Tunisia's ecosystems are delicate, from the coral reefs of the Mediterranean coast to the fragile desert environments of the Sahara. Mass tourism can strain these resources, while thoughtful tourism can actually help preserve them. Here's how to be part of the solution.

## Choose Eco-Certified Accommodations

Look for hotels and guesthouses that have implemented sustainable practices:
- Solar-powered energy systems
- Water conservation measures
- Waste recycling programs
- Use of local, organic produce
- Employment of local staff

Many traditional riads and eco-lodges in Tunisia have embraced these practices while offering authentic cultural experiences. The extra research is worth it—these properties often provide the most memorable stays.

## Support Local Communities

One of the most impactful things you can do is put your tourist dollars directly into local hands:

**Buy Directly from Artisans**: Skip the big tourist shops and buy pottery, textiles, and crafts directly from the people who make them. You'll pay fair prices and hear the stories behind each piece.

**Eat at Local Restaurants**: Family-run restaurants serving traditional Tunisian cuisine offer better food at better prices than tourist-focused establishments. Plus, you're supporting local livelihoods.

**Hire Local Guides**: Certified local guides provide employment in their communities and offer insights no guidebook can match.

## Respect Water Resources

Tunisia faces significant water scarcity. As a visitor, you can help:
- Take shorter showers
- Reuse towels in hotels
- Avoid hotels with water-intensive features like golf courses
- Don't leave taps running
- Support restaurants that serve local wine and beverages rather than imported bottled water

## Desert Tourism Done Right

The Sahara is Tunisia's crown jewel, but it's also incredibly fragile. When booking desert tours:
- Choose operators who practice Leave No Trace principles
- Avoid tours that allow motorized vehicles off designated paths
- Stay in traditional Berber camps rather than permanent structures
- Participate in camel treks rather than 4x4 desert racing
- Never remove rocks, plants, or artifacts as souvenirs

## Wildlife and Nature

Tunisia is home to unique wildlife, from sea turtles to desert foxes. Responsible wildlife viewing means:
- Keeping a respectful distance from animals
- Never feeding wildlife
- Avoiding tours that promise guaranteed animal encounters
- Supporting conservation projects when possible
- Reporting injured animals to local authorities

## Plastic-Free Travel

Tunisia, like many countries, struggles with plastic pollution. You can help by:
- Bringing a reusable water bottle and refilling it
- Carrying reusable shopping bags
- Refusing plastic straws and utensils
- Buying products with minimal packaging
- Participating in beach cleanups if you're visiting coastal areas

## Cultural Sensitivity

Sustainable tourism includes cultural sustainability:
- Learn basic Arabic or French phrases
- Dress modestly, especially in rural areas and religious sites
- Ask permission before photographing people
- Respect prayer times and religious customs
- Learn about and respect local traditions

## Carbon Offsetting Your Trip

Air travel has a significant carbon footprint. While we can't eliminate it entirely, we can offset it:
- Choose direct flights when possible
- Use public transportation or bicycles instead of taxis
- Walk when exploring cities
- Consider staying longer in one place rather than hopping between destinations
- Support reforestation projects in Tunisia

## The Ripple Effect

Every sustainable choice you make creates a ripple effect. When hotels see that travelers value sustainability, they invest in green practices. When restaurants notice tourists seeking local cuisine, they preserve traditional recipes. When guides see interest in conservation, they become ambassadors for protecting natural areas.

Your vacation can be part of ensuring that Tunisia's beauty endures for future generations. The best part? Sustainable travel often leads to more authentic, meaningful experiences that you'll remember long after you return home.`,
  },
  {
    slug: 'tunisian-cuisine-guide',
    title: "A Food Lover's Guide to Tunisian Cuisine: From Couscous to Brik",
    excerpt:
      "Discover the rich flavors, aromatic spices, and culinary traditions that make Tunisian food one of the Mediterranean's best-kept secrets.",
    coverImage: 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1920',
    date: 'November 28, 2024',
    author: {
      name: 'Leila Ben Ahmed',
      avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=200',
      bio: 'Culinary historian and cookbook author specializing in North African cuisine. Chef at a award-winning restaurant in Tunis.',
    },
    category: 'Food & Culture',
    tags: ['Food', 'Cuisine', 'Culture', 'Recipes', 'Traditions'],
    readTime: '12 min read',
    content: `Tunisian cuisine is a delicious fusion of Berber, Arab, Turkish, and Mediterranean influences, creating a unique culinary tradition that deserves far more recognition than it receives. From the fiery harissa that accompanies almost every meal to the delicate sweetness of makroud pastries, every dish tells a story of Tunisia's rich cultural heritage.

## The Foundation: Harissa

No exploration of Tunisian food can begin without harissa, the fiery chili paste that's as essential to Tunisian cuisine as olive oil is to Italian. Made from dried red peppers, garlic, olive oil, and spices like caraway and coriander, harissa adds depth and heat to countless dishes.

Every Tunisian family has their own harissa recipe, passed down through generations. You'll find it served with bread as an appetizer, mixed into stews, or used as a marinade for grilled meats. The best harissa is homemade, but even store-bought versions far surpass any other hot sauce in complexity.

## Brik: The Perfect Street Food

Imagine biting into a crispy, golden-fried pastry, only to have a perfectly runny egg yolk spill out, mingling with tuna, capers, and parsley. That's brik, Tunisia's most iconic street food. The art of making brik lies in achieving the perfect crispiness of the ultra-thin malsouka pastry while keeping the egg just right.

The most traditional version, brik à l'oeuf, contains a whole egg, but variations include potato, seafood, or meat fillings. Eating brik is an art form itself—you must bite carefully to avoid the yolk explosion, though some argue that's half the fun.

## Couscous: Friday Tradition

In Tunisia, Friday means couscous. This isn't just a meal; it's a social ritual bringing families together after Friday prayers. Tunisian couscous differs from its Moroccan counterpart—it's typically steamed three times for exceptional fluffiness and served with a rich stew of vegetables, chickpeas, and meat.

The vegetables are carefully selected and arranged: pumpkin for sweetness, turnips for earthiness, zucchini for freshness, and chickpeas for protein. Lamb or chicken simmers in a fragrant broth spiced with cumin, coriander, and caraway. Each family adds their special touch—some include raisins for sweetness, others add hot peppers for kick.

## Tajine Tunisien: Not What You Think

If you've traveled in Morocco, Tunisia's "tajine" will surprise you. While Moroccan tajine is a slow-cooked stew, Tunisian tajine is more like a frittata or quiche. Eggs bind together ingredients like meat, vegetables, cheese, and herbs, then the mixture is baked until golden and set.

Popular varieties include tajine djerbien (with seafood), tajine malsouka (layered with pastry), and tajine merguez (with spicy sausage). It's comfort food at its finest, perfect for using up leftovers and ideal for any meal of the day.

## Lablabi: Breakfast of Champions

Start your morning like a Tunisian with lablabi, a humble yet deeply satisfying chickpea soup. Served steaming hot in earthenware bowls, lablabi features chickpeas in a garlicky, cumin-spiced broth, topped with pieces of stale bread that soak up the liquid.

But the magic happens with the condiments: a drizzle of olive oil, a spoonful of harissa, a squeeze of lemon, maybe some tuna or a poached egg. Every cafe has their own style, and locals have fierce loyalties to their favorite lablabi spot.

## Mechouia Salad: Grilled Perfection

This smoky, grilled vegetable salad showcases the Tunisian love for charred flavors. Tomatoes, peppers, and onions are grilled over open flames until blackened, then peeled and chopped with capers, preserved lemon, and tuna. The result is a salad that's far more than the sum of its parts.

Mechouia appears as a mezze dish before larger meals, spread on bread as a sandwich filling, or served alongside grilled fish or meat. The key is achieving the right level of char without burning, imparting a subtle smokiness that makes this dish unforgettable.

## Sweet Traditions

Tunisian pastries are heavily influenced by Ottoman cuisine, resulting in wonderfully sweet, honey-soaked treats perfect with mint tea.

**Makroud**: Semolina-based cookies filled with dates, deep-fried, and dipped in honey. The contrast between the crispy exterior and soft, sweet filling is addictive.

**Baklawa**: While similar to other Mediterranean versions, Tunisian baklawa uses almonds or pistachios and is flavored with rose or orange blossom water.

**Bambalouni**: Tunisia's answer to donuts, these deep-fried rings of dough are sold by beach vendors and best eaten fresh and warm.

## Mint Tea Culture

No meal is complete without Tunisian mint tea. Unlike Moroccan tea which is green tea with mint, Tunisian tea typically uses black tea with an abundance of fresh mint leaves and pine nuts floating on top. It's sweet, refreshing, and the perfect ending to any meal.

Tea drinking is a social activity. Tunisians will spend hours at cafes, sipping tea and playing cards or backgammon. Refusing tea when offered is considered impolite—even if you've just had three cups elsewhere.

## Where to Eat

**For Street Food**: Head to Tunis's medina, where vendors sell brik, merguez sandwiches, and fricassé (fried bread stuffed with tuna and vegetables).

**For Traditional Restaurants**: Look for family-run establishments serving home-style cooking. The less the menu resembles international cuisine, the better.

**For Markets**: Souk el-Blaghgia in Tunis and markets in Nabeul and Kairouan offer incredible ingredients and prepared foods.

## Bringing Tunisia Home

Many traditional ingredients can be found in Middle Eastern or North African grocery stores:
- Harissa (or make your own!)
- Rose water and orange blossom water
- Dried dates for makroud
- Malsouka or phyllo pastry for brik
- Tunisian olive oil, considered among the world's finest

Tunisian cuisine rewards the adventurous eater. Every dish has regional variations, family secrets, and stories attached. The best way to experience it? Follow your nose, trust local recommendations, and never be afraid to try something new. Your taste buds will thank you.`,
  },
];

export function getAllBlogPosts(): BlogPost[] {
  return blogPosts;
}

export function getBlogPostBySlug(slug: string): BlogPost | undefined {
  return blogPosts.find((post) => post.slug === slug);
}

export function getBlogPostsByCategory(category: string): BlogPost[] {
  return blogPosts.filter((post) => post.category === category);
}
