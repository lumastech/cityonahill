<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

/**
 * Image placeholders — set each entry to the path of the real image
 * (e.g. '/images/landing/hero-careers-day.jpg' after dropping the file
 * in public/images/landing/). Empty entries render a labelled grey
 * placeholder so the layout stays intact.
 */
const images = {
    crest: '/logo.jpeg',            // School crest / logo (square, shown at 52px in the nav + footer)
    heroCareersDay: '/images/coh9.jpeg',   // Hero collage — learners and teachers on Careers Day
    heroHeritageDay: '/images/coh9.jpeg',  // Hero collage — learners in traditional wear on Heritage Day
    heroReading: '/images/coh15.jpeg',      // Hero collage — learners reading together in class
    aboutClassroom: '/images/coh15.jpeg',   // About section — learners in class
    marchingBand: '/images/coh12.jpeg',     // Activities section — the school marching band
    galPajamaDay: '/images/coh4.jpeg',     // Gallery (tall tile) — Pajama Day
    galAirportTrip: '/images/coh14.jpeg',   // Gallery — trip to Kenneth Kaunda International Airport
    galMuseumVisit: '/images/coh10.jpeg',   // Gallery — museum visit
    galHeritageDay: '/images/coh11.jpeg',   // Gallery — Heritage Day celebrations
    galCosySmiles: '/images/coh15.jpeg',    // Gallery — young learners in cosy onesies
    galIndependence: '/images/coh13.jpeg',    // Gallery — young learners in cosy onesies
}

function ph(label: string, w = 800, h = 600): string {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}"><rect width="100%" height="100%" fill="#DCE7EE"/><rect x="8" y="8" width="${w - 16}" height="${h - 16}" fill="none" stroke="#9FB4C2" stroke-width="3" stroke-dasharray="14 10" rx="6"/><text x="50%" y="50%" fill="#5A7285" font-family="sans-serif" font-size="${Math.round(w / 22)}" font-weight="600" text-anchor="middle" dominant-baseline="middle">${label}</text></svg>`
    return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg)
}

const menuOpen = ref(false)
const rootEl = ref<HTMLElement | null>(null)

onMounted(() => {
    const io = new IntersectionObserver(
        entries => entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('on')
                io.unobserve(e.target)
            }
        }),
        { threshold: 0.12 },
    )
    rootEl.value?.querySelectorAll('.rv').forEach(el => io.observe(el))
})
</script>

<template>
    <Head>
        <title>City on a Hill Academy — Learning Today, Leading Tomorrow</title>
        <meta name="description" content="City on a Hill Academy is a private school in Kabanana, Lusaka offering quality, affordable education from Pre-Grade through the primary grades." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet" />
    </Head>

    <div ref="rootEl" class="coa">

        <nav>
            <div class="nav-in">
                <img :src="images.crest || ph('Crest', 200, 200)" alt="City on a Hill Academy crest" />
                <div class="brand">City on a Hill<small>Academy · Lusaka</small></div>
                <button class="menu-btn" :aria-expanded="menuOpen" @click="menuOpen = !menuOpen">Menu</button>
                <div class="nav-links" :class="{ open: menuOpen }" @click="menuOpen = false">
                    <a href="#about">About</a>
                    <a href="#academics">Academics</a>
                    <a href="#activities">Activities</a>
                    <a href="#life">School life</a>
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')">Dashboard</Link>
                    <Link v-else :href="route('login')">Portal login</Link>
                    <a href="#contact" class="btn btn-red">Enrol now</a>
                </div>
            </div>
        </nav>

        <header class="hero">
            <div class="wrap">
                <div class="rv">
                    <span class="eyebrow">Kabanana, Great North Road · Lusaka</span>
                    <h1>Learning today, <span class="u">leading tomorrow.</span></h1>
                    <p class="lead">City on a Hill Academy gives children in our community a strong start — quality, affordable education from Pre-Grade through primary, taught by caring, qualified teachers in small classes.</p>
                    <div class="hero-cta">
                        <a class="btn btn-red" href="#contact">Book a school visit</a>
                        <a class="btn btn-ghost" href="#life">See school life</a>
                    </div>
                    <p class="hero-note">Now enrolling · Pre-Grade to primary grades</p>
                </div>
                <div class="board rv" aria-hidden="false">
                    <figure class="snap s1"><img :src="images.heroCareersDay || ph('Careers Day photo')" alt="Learners and teachers on Careers Day" /><figcaption>Careers Day</figcaption></figure>
                    <figure class="snap s2"><img :src="images.heroHeritageDay || ph('Heritage Day photo')" alt="Learners in traditional wear on Heritage Day" /><figcaption>Heritage Day</figcaption></figure>
                    <figure class="snap s3"><img :src="images.heroReading || ph('Reading time photo')" alt="Learners reading together in class" /><figcaption>Reading time</figcaption></figure>
                </div>
            </div>
        </header>

        <section class="band" id="why">
            <svg class="chalk" style="top:30px;left:4%" width="90" height="90" viewBox="0 0 90 90" fill="none"><circle cx="45" cy="45" r="40" stroke="#fff" stroke-width="3" stroke-dasharray="8 10" /></svg>
            <svg class="chalk" style="bottom:26px;right:5%" width="120" height="60" viewBox="0 0 120 60" fill="none"><path d="M5 50 Q30 5 60 30 T115 15" stroke="#fff" stroke-width="3" stroke-linecap="round" /></svg>
            <div class="wrap">
                <span class="eyebrow">Why families choose us</span>
                <h2 style="margin:12px 0 14px">Built on care, discipline and big dreams</h2>
                <p class="lead" style="margin-bottom:44px">Everything we do is guided by our core values — excellence, integrity, respect, responsibility, discipline, innovation, teamwork and compassion.</p>
                <div class="hang-row">
                    <div class="hang rv"><div class="hang-card"><div class="num">1</div><h3>Caring, qualified teachers</h3><p>Dedicated staff who nurture every learner academically, socially, emotionally and spiritually.</p></div></div>
                    <div class="hang rv"><div class="hang-card"><div class="num">2</div><h3>Small class sizes</h3><p>Every child is known by name, with continuous assessment so no learner is left behind.</p></div></div>
                    <div class="hang rv"><div class="hang-card"><div class="num">3</div><h3>Safe &amp; secure environment</h3><p>Spacious, well-ventilated classrooms, secure playgrounds and clean water and sanitation.</p></div></div>
                    <div class="hang rv"><div class="hang-card"><div class="num">4</div><h3>Affordable school fees</h3><p>Quality education within reach for families in our community, with a strong parent-school partnership.</p></div></div>
                </div>
            </div>
        </section>

        <section class="sec about" id="about">
            <div class="wrap">
                <img class="rv" :src="images.aboutClassroom || ph('Classroom photo')" alt="Learners in class at City on a Hill Academy" />
                <div class="rv">
                    <span class="eyebrow">About our school</span>
                    <h2>A strong foundation for every child</h2>
                    <p>City on a Hill Academy is a private school in Zambia dedicated to quality, affordable and holistic education from Pre-Grade (Early Childhood Education) through the primary grades — with plans for continued growth into higher levels. We nurture every learner's academic potential while building strong character, creativity, leadership and lifelong learning skills.</p>
                    <div class="mvm">
                        <div><b>Vision</b><span>To be a centre of excellence that provides quality education, develops responsible citizens, and prepares learners to succeed in a dynamic global society.</span></div>
                        <div><b>Mission</b><span>Learner-centred, high-quality education through qualified teachers, innovative methods, and a supportive environment that promotes academic excellence, moral values, discipline and personal development.</span></div>
                    </div>
                    <p class="motto">"Learning Today, Leading Tomorrow."</p>
                </div>
            </div>
        </section>

        <section class="sec acad" id="academics">
            <div class="wrap">
                <span class="eyebrow">Academic programmes</span>
                <h2 style="margin:12px 0 14px">From first words to confident learners</h2>
                <p class="lead">Our programme follows the Zambian curriculum, enriched with practical learning that builds critical thinking, creativity, problem-solving and confident communication.</p>
                <div class="acad-grid">
                    <div class="acard gold rv">
                        <span class="eyebrow" style="color:var(--gold)">Early years</span>
                        <h3>Pre-Grade</h3>
                        <p>Children develop essential early literacy, numeracy, language and social skills through play-based, activity-oriented learning.</p>
                        <ul>
                            <li>Play-based, hands-on classrooms</li>
                            <li>Early reading, counting and language</li>
                            <li>Secure playgrounds for little learners</li>
                        </ul>
                    </div>
                    <div class="acard rv">
                        <span class="eyebrow">Grades 1 and up</span>
                        <h3>Primary Grades</h3>
                        <p>A balanced education aligned with the Zambian curriculum, with continuous assessment so teachers can support every learner's progress.</p>
                        <ul>
                            <li>Learner-centred, modern teaching methods</li>
                            <li>Technology and interactive resources</li>
                            <li>Library, reading and ICT opportunities</li>
                        </ul>
                    </div>
                </div>
                <div class="fac rv">
                    <span>📚 Library &amp; reading corner</span>
                    <span>💻 Computer &amp; ICT learning</span>
                    <span>🏃 Sports &amp; recreation</span>
                    <span>🚸 Secure playgrounds</span>
                    <span>💧 Clean water &amp; sanitation</span>
                    <span>🌤️ Spacious classrooms</span>
                </div>
            </div>
        </section>

        <section class="sec co" id="activities">
            <div class="wrap">
                <img class="rv" :src="images.marchingBand || ph('Marching band photo')" alt="The City on a Hill Academy marching band with drums and brass instruments" />
                <div class="rv">
                    <span class="eyebrow">Beyond the classroom</span>
                    <h2>We educate the whole child</h2>
                    <p style="margin-top:14px">From our marching band to the sports field, learners build confidence, teamwork and leadership through a full life of co-curricular activities.</p>
                    <ul>
                        <li><span class="dot"></span><span><b>Music, dance &amp; drama</b> — including our own school marching band</span></li>
                        <li><span class="dot"></span><span><b>Football, netball &amp; athletics</b> and other sports</span></li>
                        <li><span class="dot"></span><span><b>Debate &amp; public speaking</b> to grow confident voices</span></li>
                        <li><span class="dot"></span><span><b>Cultural &amp; heritage activities</b> celebrating who we are</span></li>
                        <li><span class="dot"></span><span><b>Educational tours</b> — museums, the airport and beyond</span></li>
                        <li><span class="dot"></span><span><b>Community service</b> and environmental projects</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="sec gal" id="life">
            <div class="wrap">
                <span class="eyebrow">School life</span>
                <h2 style="margin:12px 0 14px">Every day is worth remembering</h2>
                <p class="lead">Careers Day, Heritage Day, Pajama Day, school trips — our calendar is full of moments that make learning joyful.</p>
                <div class="gal-grid">
                    <figure class="tile tall rv"><img :src="images.galPajamaDay || ph('Pajama Day photo', 600, 900)" alt="Learners dressed up for Pajama Day" /><figcaption>Pajama Day fun</figcaption></figure>
                    <figure class="tile rv"><img :src="images.galAirportTrip || ph('Airport trip photo')" alt="School trip to Kenneth Kaunda International Airport" /><figcaption>Trip to Kenneth Kaunda International Airport</figcaption></figure>
                    <figure class="tile rv"><img :src="images.galMuseumVisit || ph('Museum visit photo')" alt="Learners in uniform visiting the museum" /><figcaption>Museum visit — learning our history</figcaption></figure>
                    <figure class="tile rv"><img :src="images.galHeritageDay || ph('Heritage Day photo')" alt="Learners celebrating Heritage Day in traditional colours" /><figcaption>Heritage Day celebrations</figcaption></figure>
                    <figure class="tile rv"><img :src="images.galCosySmiles || ph('Cosy season photo')" alt="Young learners smiling in cosy onesies" /><figcaption>Cosy season smiles</figcaption></figure>
                    <figure class="tile rv"><img :src="images.galIndependence || ph('Independence Day photo')" alt="Learners celebrating Independence Day" /><figcaption>Independence Day celebrations</figcaption></figure>
                </div>
            </div>
        </section>

        <section class="sec contact" id="contact">
            <div class="wrap">
                <div class="rv">
                    <span class="eyebrow">Get in touch</span>
                    <h2 style="margin:12px 0 8px">Come and see us</h2>
                    <p>We'd love to show you around and answer your questions about enrolment.</p>
                    <div class="c-list">
                        <div class="c-item"><div class="ic">📍</div><div><b>Visit us</b>3776 Great North Road, Kabanana, Lusaka</div></div>
                        <div class="c-item"><div class="ic">📞</div><div><b>Call us</b><a href="tel:+260976063202">+260 976 063 202</a></div></div>
                        <div class="c-item"><div class="ic">💬</div><div><b>WhatsApp</b><a href="https://wa.me/260976063202">+260 976 063 202</a></div></div>
                        <div class="c-item"><div class="ic">✉️</div><div><b>Email</b><a href="mailto:madalitsolungu89@gmail.com">madalitsolungu89@gmail.com</a></div></div>
                    </div>
                </div>
                <div class="visit-card rv">
                    <h3>Enrolment is open</h3>
                    <p>Message us on WhatsApp to book a visit, ask about fees, or reserve your child's place for the coming term. We reply quickly during school hours.</p>
                    <a class="btn btn-wa" href="https://wa.me/260976063202?text=Hello%2C%20I%20would%20like%20to%20enquire%20about%20enrolment%20at%20City%20on%20a%20Hill%20Academy.">Chat on WhatsApp</a>
                    <a class="btn btn-ghost" href="tel:+260976063202">Call the school</a>
                </div>
            </div>
        </section>

        <footer>
            <div class="wrap">
                <img :src="images.crest || ph('Crest', 200, 200)" alt="" />
                <div><b>City on a Hill Academy</b><br />3776 Great North Road, Kabanana, Lusaka</div>
                <div class="right">"Learning Today, Leading Tomorrow." · © 2026 City on a Hill Academy · <Link :href="route('login')" style="color:inherit">Portal login</Link></div>
            </div>
        </footer>

    </div>
</template>

<style>
html { scroll-behavior: smooth; }
</style>

<style scoped>
.coa{
  --sky:#2BAFD4;
  --sky-deep:#1B93B6;
  --navy:#17255A;
  --ink:#1B2340;
  --gold:#F6B31B;
  --red:#D5273B;
  --paper:#FFFDF7;
  --cloud:#EAF7FB;
  --line:rgba(23,37,90,.14);
  --disp:'Bricolage Grotesque', sans-serif;
  --body:'Figtree', sans-serif;
  font-family:var(--body);color:var(--ink);background:var(--paper);line-height:1.6;font-size:17px;
  min-height:100vh;
}
.coa *{box-sizing:border-box}
img{display:block;max-width:100%}
a{color:inherit}
.wrap{max-width:1120px;margin:0 auto;padding:0 24px}
.eyebrow{font-weight:700;font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:var(--sky-deep)}
h1,h2,h3{font-family:var(--disp);line-height:1.08;color:var(--navy)}
h2{font-size:clamp(30px,4.4vw,44px);font-weight:800;letter-spacing:-.01em}
.sec{padding:88px 0}
.lead{font-size:19px;max-width:62ch}
.btn{display:inline-block;font-weight:700;font-size:16px;text-decoration:none;padding:14px 26px;border-radius:999px;transition:transform .15s ease, box-shadow .15s ease}
.btn:focus-visible,a:focus-visible{outline:3px solid var(--gold);outline-offset:2px}
.btn-red{background:var(--red);color:#fff;box-shadow:0 6px 0 #A31B2C}
.btn-red:hover{transform:translateY(-2px)}
.btn-ghost{border:2px solid var(--navy);color:var(--navy)}
.btn-ghost:hover{background:var(--navy);color:#fff}
.btn-wa{background:#1EBE5D;color:#fff;box-shadow:0 6px 0 #14813F}
.btn-wa:hover{transform:translateY(-2px)}

/* ---------- NAV ---------- */
nav{position:sticky;top:0;z-index:50;background:rgba(255,253,247,.92);backdrop-filter:blur(8px);border-bottom:1px solid var(--line)}
.nav-in{display:flex;align-items:center;gap:14px;padding:12px 24px;max-width:1120px;margin:0 auto;position:relative}
.nav-in img{width:52px;height:52px;object-fit:contain}
.brand{font-family:var(--disp);font-weight:800;font-size:19px;color:var(--navy);line-height:1.05}
.brand small{display:block;font-family:var(--body);font-weight:600;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--red)}
.nav-links{margin-left:auto;display:flex;gap:26px;align-items:center}
.nav-links a{text-decoration:none;font-weight:600;font-size:15px;color:var(--ink)}
.nav-links a:hover{color:var(--sky-deep)}
.nav-links .btn{padding:10px 20px;font-size:14px;color:#fff}
.menu-btn{display:none;margin-left:auto;background:none;border:2px solid var(--navy);border-radius:10px;padding:6px 12px;font-weight:700;font-family:var(--body);font-size:14px;color:var(--navy);cursor:pointer}

/* ---------- HERO ---------- */
.hero{overflow:hidden;position:relative;padding:72px 0 96px}
.hero .wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center}
.hero h1{font-size:clamp(40px,5.8vw,68px);font-weight:800;letter-spacing:-.015em;margin:14px 0 20px}
.hero h1 .u{position:relative;white-space:nowrap}
.hero h1 .u::after{content:"";position:absolute;left:0;right:0;bottom:6px;height:.32em;background:var(--gold);z-index:-1;border-radius:3px;transform:rotate(-1deg)}
.hero p{margin-bottom:30px}
.hero-cta{display:flex;gap:14px;flex-wrap:wrap}
.hero-note{margin-top:22px;font-size:14px;color:#5A6180;font-weight:500}

/* pinboard collage */
.board{position:relative;height:520px}
.snap{position:absolute;background:#fff;padding:10px 10px 34px;box-shadow:0 14px 30px rgba(23,37,90,.18);border-radius:4px;margin:0}
.snap img{border-radius:2px;width:100%;height:100%;object-fit:cover}
.snap figcaption{position:absolute;bottom:8px;left:0;right:0;text-align:center;font-family:var(--disp);font-weight:700;font-size:13px;color:var(--navy)}
.snap::before{content:"";position:absolute;top:-12px;left:50%;transform:translateX(-50%) rotate(-3deg);width:86px;height:26px;background:rgba(246,179,27,.75);border-radius:2px}
.s1{width:62%;height:300px;top:0;left:0;transform:rotate(-3deg)}
.s2{width:48%;height:260px;top:70px;right:0;transform:rotate(2.5deg)}
.s3{width:56%;height:230px;bottom:0;left:14%;transform:rotate(-1deg)}

/* ---------- SKY BAND: hanging value cards ---------- */
.band{background:var(--sky);color:#fff;position:relative;padding:78px 0 92px;overflow:hidden}
.band .eyebrow{color:#FFE9A8}
.band h2{color:#fff}
.band p.lead{color:#EAFBFF}
.chalk{position:absolute;opacity:.35;pointer-events:none}
.hang-row{display:grid;grid-template-columns:repeat(4,1fr);gap:26px;margin-top:8px}
.hang{position:relative;padding-top:44px}
.hang::before{content:"";position:absolute;top:0;left:50%;width:2px;height:44px;background:rgba(255,255,255,.85)}
.hang::after{content:"";position:absolute;top:-4px;left:50%;transform:translateX(-50%);width:10px;height:10px;border-radius:50%;background:#fff}
.hang-card{background:var(--paper);color:var(--ink);border-radius:14px;padding:22px 20px;box-shadow:0 12px 24px rgba(11,58,72,.28)}
.hang:nth-child(1) .hang-card{transform:rotate(-2deg)}
.hang:nth-child(2) .hang-card{transform:rotate(1.5deg)}
.hang:nth-child(3) .hang-card{transform:rotate(-1deg)}
.hang:nth-child(4) .hang-card{transform:rotate(2deg)}
.hang-card .num{font-family:var(--disp);font-weight:800;font-size:30px;color:var(--red)}
.hang-card h3{font-size:19px;margin:4px 0 6px}
.hang-card p{font-size:15px;color:#41497A;line-height:1.5}

/* ---------- ABOUT ---------- */
.about .wrap{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.about img{border-radius:18px;box-shadow:0 18px 40px rgba(23,37,90,.16);border:6px solid #fff}
.about h2{margin:12px 0 18px}
.mvm{margin-top:26px;display:grid;gap:14px}
.mvm div{border-left:4px solid var(--gold);padding:4px 0 4px 16px}
.mvm b{font-family:var(--disp);color:var(--navy)}
.mvm span{display:block;font-size:15.5px;color:#41497A}
.motto{margin-top:24px;font-family:var(--disp);font-weight:700;font-size:21px;color:var(--red)}

/* ---------- ACADEMICS ---------- */
.acad{background:var(--cloud)}
.acad-grid{display:grid;grid-template-columns:1fr 1fr;gap:26px;margin-top:40px}
.acard{background:#fff;border-radius:18px;padding:34px 30px;border-top:6px solid var(--sky);box-shadow:0 10px 26px rgba(23,37,90,.08)}
.acard.gold{border-top-color:var(--gold)}
.acard h3{font-size:24px;margin-bottom:10px}
.acard ul{list-style:none;margin-top:14px;padding:0}
.acard li{padding-left:26px;position:relative;margin-bottom:8px;font-size:15.5px}
.acard li::before{content:"✓";position:absolute;left:0;color:var(--sky-deep);font-weight:800}
.fac{margin-top:34px;display:flex;flex-wrap:wrap;gap:10px}
.fac span{background:#fff;border:1.5px solid var(--line);border-radius:999px;padding:8px 16px;font-size:14px;font-weight:600;color:var(--navy)}

/* ---------- CO-CURRICULAR ---------- */
.co .wrap{display:grid;grid-template-columns:1.1fr .9fr;gap:56px;align-items:center}
.co img{border-radius:18px;box-shadow:0 18px 40px rgba(23,37,90,.18)}
.co ul{list-style:none;margin-top:20px;display:grid;gap:10px;padding:0}
.co li{display:flex;gap:12px;align-items:flex-start;font-size:16px}
.co li b{color:var(--navy)}
.dot{flex:none;margin-top:7px;width:10px;height:10px;border-radius:50%;background:var(--red)}
.co li:nth-child(2n) .dot{background:var(--gold)}
.co li:nth-child(3n) .dot{background:var(--sky)}

/* ---------- GALLERY ---------- */
.gal{background:var(--navy);color:#fff}
.gal .eyebrow{color:var(--gold)}
.gal h2{color:#fff}
.gal p.lead{color:#C9D2F2}
.gal-grid{margin-top:44px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.tile{position:relative;border-radius:14px;overflow:hidden;box-shadow:0 12px 26px rgba(0,0,0,.3);margin:0}
.tile img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.tile:hover img{transform:scale(1.04)}
.tile.tall{grid-row:span 2}
.tile figcaption{position:absolute;left:0;right:0;bottom:0;padding:34px 16px 12px;font-weight:700;font-size:15px;background:linear-gradient(transparent,rgba(11,17,44,.85))}
.gal-grid .tile{min-height:210px}

/* ---------- CONTACT ---------- */
.contact .wrap{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start}
.c-list{margin-top:26px;display:grid;gap:18px}
.c-item{display:flex;gap:16px;align-items:flex-start}
.c-item .ic{flex:none;width:46px;height:46px;border-radius:12px;background:var(--cloud);display:grid;place-items:center;font-size:20px}
.c-item b{display:block;font-family:var(--disp);color:var(--navy)}
.c-item a{color:var(--sky-deep);font-weight:600;text-decoration:none}
.c-item a:hover{text-decoration:underline}
.visit-card{background:var(--sky);color:#fff;border-radius:20px;padding:38px 34px;box-shadow:0 18px 40px rgba(27,147,182,.35)}
.visit-card h3{color:#fff;font-size:26px;margin-bottom:12px}
.visit-card p{color:#EAFBFF;margin-bottom:24px}
.visit-card .btn{margin-right:10px;margin-bottom:10px}
.visit-card .btn-ghost{border-color:#fff;color:#fff}
.visit-card .btn-ghost:hover{background:#fff;color:var(--sky-deep)}

/* ---------- FOOTER ---------- */
footer{background:var(--navy);color:#B9C3E8;padding:44px 0;font-size:14px}
footer .wrap{display:flex;gap:20px;align-items:center;flex-wrap:wrap}
footer img{width:44px;height:44px;border-radius:8px;background:#fff;padding:3px}
footer b{color:#fff;font-family:var(--disp)}
footer .right{margin-left:auto}

/* reveal animation */
.rv{opacity:0;transform:translateY(18px);transition:opacity .6s ease, transform .6s ease}
.rv.on{opacity:1;transform:none}
@media (prefers-reduced-motion: reduce){.rv{opacity:1;transform:none;transition:none}.tile img{transition:none}}

@media (max-width:900px){
  .hero .wrap,.about .wrap,.co .wrap,.contact .wrap{grid-template-columns:1fr}
  .board{height:440px;margin-top:10px}
  .hang-row{grid-template-columns:1fr 1fr}
  .acad-grid{grid-template-columns:1fr}
  .gal-grid{grid-template-columns:1fr 1fr}
  .nav-links{display:none;position:absolute;top:100%;left:0;right:0;background:var(--paper);flex-direction:column;padding:18px 24px;border-bottom:1px solid var(--line);gap:16px;align-items:flex-start}
  .nav-links.open{display:flex}
  .menu-btn{display:block}
}
@media (max-width:560px){
  .hang-row{grid-template-columns:1fr}
  .gal-grid{grid-template-columns:1fr}
  .board{height:400px}
  .sec{padding:64px 0}
}
</style>
