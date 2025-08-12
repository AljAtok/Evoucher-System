<script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8; /* Light blue-gray background */
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align to top initially */
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section-title {
            font-size: 1.75rem; /* 28px */
            font-weight: 700;
            color: #2c3e50; /* Dark blue-gray */
            margin-bottom: 15px;
            text-align: center;
        }

        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .input-group input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 2px solid #cbd5e1; /* Slate-300 */
            border-radius: 12px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            border-color: #6366f1; /* Indigo-500 */
        }

        .btn {
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #6366f1; /* Indigo-500 */
            color: #ffffff;
            border: none;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: #4f46e5; /* Indigo-600 */
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-danger {
            background-color: #ef4444; /* Red-500 */
            color: #ffffff;
            border: none;
        }

        .btn-danger:hover:not(:disabled) {
            background-color: #dc2626; /* Red-600 */
            box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-secondary {
            background-color: #e2e8f0; /* Slate-200 */
            color: #334155; /* Slate-700 */
            border: none;
        }

        .btn-secondary:hover:not(:disabled) {
            background-color: #cbd5e1; /* Slate-300 */
            box-shadow: 0 6px 15px rgba(226, 232, 240, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .participants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background-color: #f8fafc; /* Slate-50 */
        }

        .participant-card {
            background-color: #ffffff;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: #475569; /* Slate-600 */
            border: 1px solid #e2e8f0;
        }

        .participant-card button {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.2s ease;
        }

        .participant-card button:hover {
            color: #dc2626;
        }

        .raffle-animation-area {
            position: relative;
            width: 100%;
            height: 80px; /* Adjusted height for single row of boxes */
            background-color: #e0f2fe; /* Light blue */
            border-radius: 15px;
            overflow: hidden;
            display: flex; /* Use flex for horizontal layout */
            justify-content: center;
            align-items: center;
            gap: 5px; /* Smaller gap between char boxes */
            padding: 10px;
            box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.08);
            border: 2px solid #90cdf4; /* Blue-300 */
        }

        .raffle-char-box {
            background-color: #ffffff;
            border: 2px solid #a78bfa; /* Purple-400 */
            border-radius: 8px; /* Slightly smaller radius for char boxes */
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem; /* Larger font for single character */
            font-weight: 700;
            color: #4c1d95; /* Purple-800 */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-out; /* For highlight effect */
            width: 50px; /* Fixed width for square boxes */
            height: 50px; /* Fixed height for square boxes */
            flex-shrink: 0; /* Prevent shrinking */
            overflow: hidden; /* Hide overflowing content for inner spinner */
            position: relative; /* For absolute positioning of inner spinner */
        }

        .char-spinner {
            position: absolute;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            will-change: transform; /* Optimize for animation */
            transition: transform 0s linear; /* Default no transition for rapid changes */
        }

        .char-item {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 50px; /* Must match raffle-char-box height */
            flex-shrink: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }


        .raffle-char-box.winner-highlight {
            background-color: #dcfce7; /* Green-100 */
            border-color: #22c55e; /* Green-500 */
            color: #16a34a; /* Green-700 */
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
        }

        .winner-display {
            background-color: #dcfce7; /* Green-100 */
            border: 2px solid #22c55e; /* Green-500 */
            color: #16a34a; /* Green-700 */
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
            display: none; /* Hidden by default */
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        .winner-display.show {
            display: block;
            opacity: 1;
            transform: scale(1);
        }

        .message-box {
            padding: 15px;
            border-radius: 12px;
            margin-top: 15px;
            font-weight: 600;
            text-align: center;
        }

        .message-box.error {
            background-color: #fee2e2; /* Red-100 */
            color: #dc2626; /* Red-600 */
            border: 1px solid #ef4444;
        }

        .message-box.info {
            background-color: #e0f2fe; /* Blue-100 */
            color: #2563eb; /* Blue-600 */
            border: 1px solid #3b82f6;
        }

        /* Confetti Animation (simple CSS version) */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: var(--color);
            opacity: 0;
            animation: confetti-fall 3s forwards;
            border-radius: 50%;
            pointer-events: none;
        }

        @keyframes confetti-fall {
            0% {
                transform: translate(var(--x), var(--y)) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            100% {
                transform: translate(var(--x-end), var(--y-end)) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
    <div class="container">
        <h1 class="section-title">🎉 Raffle Draw Application 🎉</h1>

        <!-- <div class="input-group">
            <input type="text" id="participantName" placeholder="Enter participant name" class="flex-grow">
            <button id="addParticipantBtn" class="btn btn-primary">Add Participant</button>
        </div> -->

        

        <div class="flex justify-center mt-6">
            <button id="drawRaffleBtn" class="btn btn-primary text-xl" disabled>
                Draw Winner!
            </button>
        </div>

		
        <div id="raffle-animation-area" class="raffle-animation-area mt-8">
			<!-- 9 Raffle Boxes will be dynamically created here -->
        </div>
		
		<div id="messageBox" class="message-box hidden"></div>

        <div id="winnerDisplay" class="winner-display">
            <!-- Winner will be displayed here -->
        </div>

		<h2 class="section-title text-xl">Current Participants</h2>
        <div id="participantsList" class="participants-grid">
            <!-- Participants will be loaded here -->
            <div class="text-gray-500 text-center col-span-full" id="noParticipantsMessage">No participants yet. Add some!</div>
        </div>
    </div>
	<script>
	let baseUrl = $("#base_url").val();

const API_BASE_URL = baseUrl; // Adjust if your CI4 backend is on a different URL/port

// --- DOM Elements ---
const participantNameInput = document.getElementById("participantName");
const addParticipantBtn = document.getElementById("addParticipantBtn");
const participantsListDiv = document.getElementById("participantsList");
const noParticipantsMessage = document.getElementById("noParticipantsMessage");
const drawRaffleBtn = document.getElementById("drawRaffleBtn");
const messageBox = document.getElementById("messageBox");
const raffleAnimationArea = document.getElementById("raffle-animation-area");
const winnerDisplay = document.getElementById("winnerDisplay");

let participants = []; // Array to store current participants

// --- Utility Functions ---

function showMessage(message, type = "info", do_not_Hide = false) {
	messageBox.textContent = message;
	messageBox.className = `message-box ${type}`;
	messageBox.classList.remove("hidden");
	if (!do_not_Hide) {
		setTimeout(() => {
			messageBox.classList.add("hidden");
		}, 5000); // Hide after 5 seconds
	}
}

function enableDrawButton() {
	drawRaffleBtn.disabled = participants.length === 0;
}

// --- API Calls ---

async function fetchParticipants() {
	try {
		const response = await fetch(`${API_BASE_URL}/participants`);
		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`);
		}
		const data = await response.json();
		participants = data;
		renderParticipants();
		enableDrawButton();
	} catch (error) {
		console.error("Error fetching participants:", error);
		showMessage(
			"Failed to load participants. Please check the backend.",
			"error"
		);
	}
}

async function addParticipant(name) {
	try {
		addParticipantBtn.disabled = true;
		const response = await fetch(`${API_BASE_URL}/participants`, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({ name: name }),
		});

		const result = await response.json();

		if (!response.ok) {
			throw new Error(result.messages.error || "Failed to add participant");
		}

		showMessage(result.messages.success, "info");
		participantNameInput.value = ""; // Clear input
		fetchParticipants(); // Refresh list
	} catch (error) {
		console.error("Error adding participant:", error);
		showMessage(`Error: ${error.message}`, "error");
	} finally {
		addParticipantBtn.disabled = false;
	}
}

async function deleteParticipant(id) {
	showMessage("Deleting participant...", "info");
	try {
		const response = await fetch(`${API_BASE_URL}/participants/${id}`, {
			method: "DELETE",
		});

		const result = await response.json();

		if (!response.ok) {
			throw new Error(result.messages.error || "Failed to delete participant");
		}

		showMessage(result.messages.success, "info");
		fetchParticipants(); // Refresh list
	} catch (error) {
		console.error("Error deleting participant:", error);
		showMessage(`Error: ${error.message}`, "error");
	}
}

async function drawWinner() {
	try {
		drawRaffleBtn.disabled = true;
		showMessage("Drawing winner...", "info");
		winnerDisplay.classList.remove("show");

		// Clear previous boxes and ensure no highlight
		raffleAnimationArea.innerHTML = "";
		const raffleCharBoxes = [];
		const charSpinners = [];

		const itemHeight = 50;
		const revealDelayPerChar = 300;
		const animationDuration = 9000;
		const totalAnimationTime = animationDuration + revealDelayPerChar * 8;

		for (let i = 0; i < 9; i++) {
			const box = document.createElement("div");
			box.className = "raffle-char-box";
			const spinner = document.createElement("div");
			spinner.className = "char-spinner";
			box.appendChild(spinner);
			raffleAnimationArea.appendChild(box);
			raffleCharBoxes.push(box);
			charSpinners.push(spinner);
		}

		const participantsWithRef = participants.filter(
			(p) => p.ref_no && p.ref_no.length === 9
		);
		if (participantsWithRef.length === 0) {
			showMessage(
				"No participants with valid 9-character reference numbers to draw from!",
				"error"
			);
			drawRaffleBtn.disabled = false;
			return;
		}

		const numericChars = "0123456789".split("");
		const alphaChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
		const alphaNumericChars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
		const spinLength = 200;
		const extraChars = 200;

		// Fetch winner from backend during the initial setup
		const apiResponse = await fetch(`${API_BASE_URL}/draw`, {
			method: "POST",
			headers: { "Content-Type": "application/json" },
		});
		const result = await apiResponse.json();
		if (!apiResponse.ok) {
			charSpinners.forEach((spinner) => (spinner.style.transition = "none"));
			throw new Error(result.messages.error || "Failed to draw winner");
		}
		const winner = result.winner;
		if (!winner.ref_no || winner.ref_no.length !== 9) {
			charSpinners.forEach((spinner) => (spinner.style.transition = "none"));
			showMessage("Winner's reference number is invalid or missing!", "error");
			drawRaffleBtn.disabled = false;
			return;
		}
		const winnerRefChars = winner.ref_no.split("");
		const spinDuration = 7000;

		// Populate each spinner with random characters, then the winning char, then extra random chars
		charSpinners.forEach((spinner, index) => {
			let charsToSpin = [];
			// let charSet = index < 6 ? numericChars : alphaChars;
			let charSet = index < 6 ? numericChars : alphaNumericChars;
			for (let i = 0; i < spinLength; i++) {
				charsToSpin.push(charSet[Math.floor(Math.random() * charSet.length)]);
			}
			charsToSpin.push(winnerRefChars[index]);
			for (let i = 0; i < extraChars; i++) {
				charsToSpin.push(charSet[Math.floor(Math.random() * charSet.length)]);
			}
			spinner.innerHTML = charsToSpin
				.map((char) => `<div class=\"char-item\">${char}</div>`)
				.join("");
			spinner.style.transition = "none";
			spinner.style.transform = `translateY(0px)`;
			spinner.offsetHeight;
		});

		// --- Animation Logic ---
		// 1. Spin all boxes as before, but always land with the winner char centered (never blank)
		// 2. After spin, immediately reflect the winning ref_no in the boxes (with highlight)
		// 3. Show 'searching for winner by ref number' effect
		// 4. Show the winning name

		await new Promise((resolve) => {
			let revealedCount = 0;
			for (let i = 0; i < raffleCharBoxes.length; i++) {
				const boxIndex = i;
				const spinner = charSpinners[boxIndex];
				const targetIndex = spinLength;
				const centerOffset =
					(raffleCharBoxes[boxIndex].offsetHeight - itemHeight) / 2;
				setTimeout(() => {
					spinner.style.transition = `transform ${spinDuration}ms cubic-bezier(0.25, 0.1, 0.25, 1)`;
					spinner.style.transform = `translateY(-${
						targetIndex * itemHeight - centerOffset
					}px)`;
					setTimeout(() => {
						spinner.style.transition = "none";
						// Immediately reflect the winning ref_no after spin
						spinner.innerHTML = `<div class='char-item'>${winnerRefChars[boxIndex]}</div>`;
						spinner.style.transform = `translateY(0px)`;
						// raffleCharBoxes[boxIndex].classList.add("winner-highlight");
						revealedCount++;
						if (revealedCount === 9) {
							raffleCharBoxes.forEach((box) =>
								box.classList.add("winner-highlight")
							);
							resolve();
						}
					}, spinDuration + 50);
				}, boxIndex * revealDelayPerChar);
			}
		});

		// Step 3: Show 'searching for winner by ref number' effect (while ref_no is already visible)
		// showMessage("Searching for winner by ref number...", "info");
		let searchingInterval;
		let dots = 0;
		winnerDisplay.innerHTML = `<span class='text-gray-500 text-lg'>Searching for winner<span id='searchingDots'></span></span>`;
		winnerDisplay.classList.add("show");
		const dotsSpan = document.getElementById("searchingDots");
		searchingInterval = setInterval(() => {
			dots = (dots + 1) % 4;
			if (dotsSpan) dotsSpan.textContent = ".".repeat(dots);
		}, 400);
		await new Promise((resolve) => setTimeout(resolve, 2500));
		clearInterval(searchingInterval);
		winnerDisplay.classList.remove("show");

		// Step 4: Show the winning name
		setTimeout(() => {
			winnerDisplay.innerHTML = `Winner: <span class=\"text-indigo-700\">${winner.name}</span>`;
			winnerDisplay.classList.add("show");
			// showMessage(`Winner drawn: ${winner.name}!`, "info");
			// showMessage(`Winner drawn: ${winner.name}!`, "info");
			createConfetti();
			drawRaffleBtn.disabled = false;
		}, 400);
		// showMessage(`Congratulations!`, "info", true);
	} catch (error) {
		console.error("Error drawing winner:", error);
		showMessage(`Error: ${error.message}", "error`);
		drawRaffleBtn.disabled = false;
		winnerDisplay.classList.remove("show");
	}
}

// --- Rendering Functions ---

function renderParticipants() {
	participantsListDiv.innerHTML = ""; // Clear current list
	if (participants.length === 0) {
		noParticipantsMessage.style.display = "block";
		participantsListDiv.appendChild(noParticipantsMessage);
	} else {
		noParticipantsMessage.style.display = "none";
		participants.forEach((p) => {
			const card = document.createElement("div");
			card.className = "participant-card";
			// Ensure ref_no is displayed in the participant list
			card.innerHTML = `
                        <span>${p.name} (${p.ref_no || "N/A"})</span>
                        
                    `;
			participantsListDiv.appendChild(card);
		});

		// Add event listeners to delete buttons
		participantsListDiv.querySelectorAll(".delete-btn").forEach((button) => {
			button.addEventListener("click", (event) => {
				const id = event.currentTarget.dataset.id;
				deleteParticipant(id);
			});
		});
	}
}

// --- Confetti Effect (Simple CSS-based) ---
function createConfetti() {
	const colors = [
		"#f87171",
		"#fbbf24",
		"#a78bfa",
		"#60a5fa",
		"#34d399",
		"#f472b6",
	]; // Tailwind colors
	const confettiCount = 50;
	const fragment = document.createDocumentFragment();

	for (let i = 0; i < confettiCount; i++) {
		const confetti = document.createElement("div");
		confetti.className = "confetti";
		confetti.style.setProperty(
			"--color",
			colors[Math.floor(Math.random() * colors.length)]
		);
		confetti.style.setProperty(
			"--x",
			`${Math.random() * window.innerWidth - window.innerWidth / 2}px`
		);
		confetti.style.setProperty("--y", `${Math.random() * -50}px`); // Start above the screen
		confetti.style.setProperty(
			"--x-end",
			`${Math.random() * window.innerWidth - window.innerWidth / 2}px`
		);
		confetti.style.setProperty("--y-end", `${window.innerHeight + 100}px`); // Fall below the screen
		confetti.style.left = "50%"; // Center horizontally initially
		confetti.style.top = "0"; // Start at the top of the viewport
		fragment.appendChild(confetti);
	}
	document.body.appendChild(fragment);

	// Clean up confetti after animation
	setTimeout(() => {
		document.querySelectorAll(".confetti").forEach((c) => c.remove());
	}, 3500); // Slightly longer than animation duration
}

// --- Event Listeners ---
// addParticipantBtn.addEventListener("click", () => {
// 	const name = participantNameInput.value.trim();
// 	if (name) {
// 		addParticipant(name);
// 	} else {
// 		showMessage("Please enter a participant name.", "error");
// 	}
// });

// participantNameInput.addEventListener("keypress", (event) => {
// 	if (event.key === "Enter") {
// 		addParticipantBtn.click();
// 	}
// });

drawRaffleBtn.addEventListener("click", drawWinner);

// --- Initial Load ---
document.addEventListener("DOMContentLoaded", fetchParticipants);

	</script>
