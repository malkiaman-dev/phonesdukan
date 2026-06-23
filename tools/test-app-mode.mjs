import { chromium, devices } from "playwright";

const baseUrl = process.argv[2] || "http://127.0.0.1/phonesdukan/";

const scenarios = [
  {
    name: "app-ua",
    userAgent: devices["Pixel 7"].userAgent + " PhonesDukanApp/1.0",
    url: baseUrl,
  },
  {
    name: "query-param",
    userAgent: devices["Pixel 7"].userAgent,
    url: baseUrl + (baseUrl.includes("?") ? "&" : "?") + "pd_app=1",
  },
  {
    name: "localstorage",
    userAgent: devices["Pixel 7"].userAgent,
    url: baseUrl,
    initScript: () => {
      try {
        localStorage.setItem("pd_app", "1");
      } catch (e) {}
    },
  },
];

let failed = false;

for (const scenario of scenarios) {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    ...devices["Pixel 7"],
    userAgent: scenario.userAgent,
  });

  if (scenario.initScript) {
    await context.addInitScript(scenario.initScript);
  }

  const page = await context.newPage();
  await page.goto(scenario.url, { waitUntil: "networkidle", timeout: 90000 });

  const metrics = await page.evaluate(() => {
    const chrome = document.getElementById("pd-site-chrome");
    const slot = document.querySelector(".pd-status-bar-slot");
    const track = document.querySelector(".pd-announcement-track");
    const install = document.getElementById("pd-install-app-btn");
    const trackStyle = track ? getComputedStyle(track) : null;
    return {
      pdApp: document.documentElement.getAttribute("data-pd-app"),
      chromePad: chrome ? getComputedStyle(chrome).paddingTop : null,
      slotH: slot ? slot.offsetHeight : null,
      trackH: track ? track.offsetHeight : 0,
      trackBg: trackStyle ? trackStyle.backgroundImage || trackStyle.backgroundColor : null,
      installExists: !!install,
      installDisplay: install ? getComputedStyle(install).display : "missing",
    };
  });

  console.log(`[${scenario.name}]`, metrics);

  const installHidden =
    !metrics.installExists ||
    metrics.installDisplay === "none" ||
    metrics.installDisplay === "hidden";

  const ok =
    metrics.pdApp === "1" &&
    metrics.chromePad === "0px" &&
    metrics.slotH === 0 &&
    metrics.trackH > 0 &&
    installHidden;

  if (!ok) {
    console.error(`FAIL [${scenario.name}]`);
    failed = true;
  }

  await page.screenshot({
    path: `tools/app-mode-${scenario.name}.png`,
    fullPage: false,
  });
  await browser.close();
}

if (failed) {
  process.exit(1);
}

console.log("PASS: all app-mode scenarios");
