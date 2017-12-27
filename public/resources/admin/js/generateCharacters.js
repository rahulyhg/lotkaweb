var levels = [
	"high", "high", "medium", "medium", "low",
  "n/a", 
	"low", "medium", "medium", "high","high"
];
var type_mapping = [0,0,0,0,0, -1, 1,1,1,1,1];

var traits_mapping = {
	"Iso vs Int":     { "types": ["Isolationist", "Isolationist"],  "levels": levels },
	"Mil vs Dem":     { "types": ["Militarist", "Democrat"],        "levels": levels },
	"Nost vs Prog":   { "types": ["Nostalgic", "Progressive"],      "levels": levels },
	"Lib vs Col":     { "types": ["Libertine", "Collectivist"],     "levels": levels },
	"Log vs Int":     { "types": ["Logical", "Intuitive"],          "levels": levels },
	"Dir vs Avoid":   { "types": ["Direct", "Avoidant"],            "levels": levels },
	"Phys vs Non-P":  { "types": ["Physical", "Non-physical"],      "levels": levels },
	"Mal vs Con":     { "types": ["Malcontent", "Content"],         "levels": levels }
};

var role_labels = [
	"ID", "Group", "Specialist Group", "Shift", "Iso vs Int", "Mil vs Dem", "Nost vs Prog", 
  "Lib vs Col", "Log vs Int", "Dir vs Avoid", "Phys vs Non-P", "Mal vs Con"
]

var rA = (a) => a[~~(Math.random()*a.length)];

function generateFromInput(input) {
  roles = input.split("\n");
  return roles.map((r, i, a, s) => (
    s = {}, 
    r.split("\t").forEach((v,i) => 
      s[role_labels[i]] = (s[role_labels[i].replace(" vs ","_")] = v, 
                          type_mapping[v]!=-1 && traits_mapping[role_labels[i]] ? 
                          rA(personalities[traits_mapping[role_labels[i]].types[type_mapping[v]]][levels[v]]) : 
                          traits_mapping[role_labels[i]] && type_mapping[v] === -1 ? false : v)
    ), s.vals = r.split("\t"), s
  ))
}