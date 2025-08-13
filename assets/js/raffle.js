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

function showMessage(
	message,
	type = "info",
	to_hide_time = 5000,
	do_not_hide = false
) {
	messageBox.textContent = message;
	messageBox.className = `message-box ${type}`;
	messageBox.classList.remove("hidden");
	if (!do_not_hide) {
		setTimeout(() => {
			messageBox.classList.add("hidden");
		}, to_hide_time);
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
		// renderParticipants();
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
		const spinLength = 200; // random chars
		const extraChars = 200; // extra chars for buffer

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

		// Populate each spinner with random alphanumeric characters, then extra random chars, then the winning char (last)
		charSpinners.forEach((spinner, index) => {
			let charsToSpin = [];
			for (let i = 0; i < spinLength; i++) {
				charsToSpin.push(
					alphaNumericChars[
						Math.floor(Math.random() * alphaNumericChars.length)
					]
				);
			}
			for (let i = 0; i < extraChars; i++) {
				charsToSpin.push(
					alphaNumericChars[
						Math.floor(Math.random() * alphaNumericChars.length)
					]
				);
			}
			charsToSpin.push(winnerRefChars[index]); // Winner char is last
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
				const targetIndex = spinLength; // Winner char is last
				const centerOffset =
					(raffleCharBoxes[boxIndex].offsetHeight - itemHeight) / 2;
				setTimeout(() => {
					spinner.style.transition = `transform ${spinDuration}ms cubic-bezier(0.25, 0.1, 0.25, 1)`;
					spinner.style.transform = `translateY(-${
						targetIndex * itemHeight - centerOffset
					}px)`;
					setTimeout(() => {
						spinner.style.transition = "none";
						// Do NOT replace spinner content, just leave it at the winner char
						spinner.style.transform = `translateY(-${
							targetIndex * itemHeight - centerOffset
						}px)`;
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
		// showMessage("Searching for winner based on the ref number...", "info");
		let searchingInterval;
		let dots = 0;
		winnerDisplay.innerHTML = `<span class='text-gray-500 text-lg'>Searching for winner based on the ref number<span id='searchingDots'></span></span>`;
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
		setTimeout(async () => {
			winnerDisplay.innerHTML = `Winner: <span class=\"text-indigo-700\">${winner.name}</span>`;
			winnerDisplay.classList.add("show");
			createConfetti();
			// await renderTabs();
			await fetchParticipants().then(renderTabs);
			drawRaffleBtn.disabled = false;
		}, 400);
		// showMessage(`Congratulations!`, "info", true);
	} catch (error) {
		console.error("Error drawing winner:", error);
		showMessage(`Error: ${error.message}`, "error");
		drawRaffleBtn.disabled = false;
		winnerDisplay.classList.remove("show");
	}
}

function createConfetti() {
	const colors = [
		"#f87171",
		"#fbbf24",
		"#a78bfa",
		"#60a5fa",
		"#34d399",
		"#f472b6",
		"#5baee2ff",
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

// --- Modal Logic ---
const modalOverlay = document.getElementById("modalOverlay");
const modalContent = document.getElementById("modalContent");
const modalActions = document.getElementById("modalActions");
function showModal(content, actionsHtml) {
	modalContent.innerHTML = content;
	modalActions.innerHTML =
		actionsHtml ||
		'<button class="btn btn-secondary" onclick="closeModal()">Close</button>';
	modalContent.style.maxHeight = "70vh";
	modalContent.style.overflowY = "auto";
	modalActions.style.position = "sticky";
	modalActions.style.bottom = "0";
	modalActions.style.background = "#fff";
	modalActions.style.zIndex = "2";
	modalActions.style.paddingTop = "12px";
	modalActions.style.marginTop = "8px";
	modalOverlay.classList.remove("hidden");
}
function closeModal() {
	modalOverlay.classList.add("hidden");
}
window.closeModal = closeModal;

// --- Add a dedicated zoom modal overlay ---
let zoomModalOverlay = document.getElementById("zoomModalOverlay");
if (!zoomModalOverlay) {
	zoomModalOverlay = document.createElement("div");
	zoomModalOverlay.id = "zoomModalOverlay";
	zoomModalOverlay.className =
		"fixed inset-0 z-[10000] flex items-center justify-center bg-black/60 hidden";
	zoomModalOverlay.innerHTML = `
		<div id="zoomModalContent" class="bg-white rounded-lg shadow-lg p-4 max-w-full max-h-full flex flex-col items-center"></div>
	`;
	document.body.appendChild(zoomModalOverlay);
}

window.zoomOrPhoto = function (imgUrl) {
	const url = decodeURIComponent(imgUrl);
	const zoomModalContent = document.getElementById("zoomModalContent");
	zoomModalContent.innerHTML = `
		<img src='${url}' alt='Zoomed OR Photo' style='max-width:90vw;max-height:70vh;border-radius:10px;box-shadow:0 4px 24px #0005;'>
		<button class='btn btn-secondary mt-4' onclick='closeZoomModal()'>Close</button>
	`;
	zoomModalOverlay.classList.remove("hidden");
};

window.closeZoomModal = function () {
	zoomModalOverlay.classList.add("hidden");
};

// --- Sortable Table Utility ---
function renderTable(container, data, columns, actions, searchPlaceholder) {
	let searchValue = "";
	let sortKey = null; // No sort by default
	let sortDir = 1;
	let page = 1;
	let pageSize = 10;
	const pageSizeOptions = [5, 10, 20, 50];
	container.innerHTML = `
        <div class="mb-2 flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                <label for="rowsPerPage" class="text-xs text-gray-600">Page rows:</label>
                <select class="rows-per-page border rounded px-1 py-0.5 text-xs">
                    ${pageSizeOptions
											.map(
												(opt) =>
													`<option value="${opt}"${
														opt === pageSize ? " selected" : ""
													}>${opt}</option>`
											)
											.join("")}
                </select>
            </div>
            <div class="table-total text-gray-600 text-sm"></div>
            <input
                type="text"
                class="search-input border rounded px-2 py-1"
                placeholder="${searchPlaceholder}"
            />
        </div>
        <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead><tr>${columns
							.map(
								(col) =>
									`<th class="px-2 py-1 border-b cursor-pointer select-none" data-key="${col.key}">${col.label} <span class="sort-indicator"></span></th>`
							)
							.join("")}<th class="px-2 py-1 border-b">Actions</th></tr></thead>
            <tbody></tbody>
        </table>
        </div>
        <div class="flex justify-between items-center mt-2">
            <div class="table-info text-gray-600 text-xs"></div>
            <div class="pagination flex gap-1"></div>
        </div>
    `;
	const tbody = container.querySelector("tbody");
	const searchInput = container.querySelector(".search-input");
	const ths = container.querySelectorAll("thead th[data-key]");
	const tableInfo = container.querySelector(".table-info");
	const tableTotal = container.querySelector(".table-total");
	const pagination = container.querySelector(".pagination");
	const rowsPerPageSelect = container.querySelector(".rows-per-page");
	let lastSorted = null;
	function renderRows() {
		let filtered = data.filter((row) =>
			columns.some((col) =>
				(row[col.key] || "")
					.toString()
					.toLowerCase()
					.includes(searchValue.toLowerCase())
			)
		);
		let displayRows = filtered;
		if (sortKey) {
			displayRows = [...filtered].sort((a, b) => {
				let va = (a[sortKey] || "").toString().toLowerCase();
				let vb = (b[sortKey] || "").toString().toLowerCase();
				if (va < vb) return -1 * sortDir;
				if (va > vb) return 1 * sortDir;
				return 0;
			});
		}
		const total = displayRows.length;
		const totalPages = Math.max(1, Math.ceil(total / pageSize));
		if (page > totalPages) page = totalPages;
		const start = (page - 1) * pageSize;
		const end = Math.min(start + pageSize, total);
		const pageRows = displayRows.slice(start, end);
		tbody.innerHTML = pageRows
			.map(
				(row) =>
					`<tr>${columns
						.map(
							(col) =>
								`<td class="px-2 py-1 border-b">${row[col.key] || ""}</td>`
						)
						.join("")}<td class="px-2 py-1 border-b">${actions(row)}</td></tr>`
			)
			.join("");
		ths.forEach((th) => {
			th.querySelector(".sort-indicator").textContent =
				th.dataset.key === sortKey ? (sortDir === 1 ? "▲" : "▼") : "";
		});
		tableInfo.textContent =
			total === 0
				? "No records found."
				: `Showing ${start + 1}–${end} of ${total}`;
		// Pagination controls
		let pagBtns = "";
		if (totalPages > 1) {
			pagBtns += `<button class='btn btn-secondary btn-xs' ${
				page === 1 ? "disabled" : ""
			} data-page='prev'>Prev</button>`;
			for (let i = 1; i <= totalPages; i++) {
				if (
					i === page ||
					i <= 2 ||
					i > totalPages - 2 ||
					Math.abs(i - page) <= 1
				) {
					pagBtns += `<button class='btn btn-xs ${
						i === page ? "btn-primary" : "btn-secondary"
					}' data-page='${i}'>${i}</button>`;
				} else if (i === 3 && page > 4) {
					pagBtns += '<span class="px-1">...</span>';
				} else if (i === totalPages - 2 && page < totalPages - 3) {
					pagBtns += '<span class="px-1">...</span>';
				}
			}
			pagBtns += `<button class='btn btn-secondary btn-xs' ${
				page === totalPages ? "disabled" : ""
			} data-page='next'>Next</button>`;
		}
		pagination.innerHTML = pagBtns;
		pagination.querySelectorAll("button[data-page]").forEach((btn) => {
			btn.onclick = () => {
				if (btn.dataset.page === "prev") page = Math.max(1, page - 1);
				else if (btn.dataset.page === "next")
					page = Math.min(totalPages, page + 1);
				else page = parseInt(btn.dataset.page);
				renderRows();
			};
		});
	}
	searchInput.addEventListener("input", (e) => {
		searchValue = e.target.value;
		page = 1;
		renderRows();
	});
	ths.forEach((th) => {
		th.addEventListener("click", () => {
			if (sortKey === th.dataset.key) sortDir *= -1;
			else {
				sortKey = th.dataset.key;
				sortDir = 1;
			}
			renderRows();
		});
	});
	rowsPerPageSelect.addEventListener("change", (e) => {
		pageSize = parseInt(e.target.value);
		page = 1;
		renderRows();
	});
	renderRows();
}

// --- Render Tabs ---
async function renderTabs() {
	// Not Validated Winners
	const notValidated = await fetchNotValidatedWinners();
	renderTable(
		tabContentNotValidated,
		notValidated,
		[
			{ label: "Ref. No.", key: "ref_no" },
			{ label: "Name", key: "name" },
			{ label: "Draw At", key: "winner_created_at" },
		],
		(row) =>
			`<button class='btn btn-primary btn-xs mr-1' onclick='confirmValidateWinner("${
				row.winner_id
			}", "${row.name}","${row.email}", "${
				row.winner_prize
			}")'>Validated</button><button class='btn btn-danger btn-xs mr-1' onclick='confirmRejectWinner("${
				row.winner_id
			}", "${
				row.name
			}")'>Reject</button><button class='btn btn-primary btn-xs' onclick='showParticipantInfo(${JSON.stringify(
				row
			)})'>View</button>`,
		"Search not validated winners..."
	);
	// Validated Winners
	const validated = await fetchValidatedWinners();
	renderTable(
		tabContentValidated,
		validated,
		[
			{ label: "Ref. No.", key: "ref_no" },
			{ label: "Name", key: "name" },
			{ label: "Validated At", key: "validated_at" },
		],
		(row) =>
			`<button class='btn btn-primary btn-xs' onclick='showParticipantInfo(${JSON.stringify(
				row
			)})'>View</button>`,
		"Search validated winners..."
	);

	// Rejected Winners
	const rejected = await fetchRejectedWinners();
	renderTable(
		tabContentRejected,
		rejected,
		[
			{ label: "Ref. No.", key: "ref_no" },
			{ label: "Name", key: "name" },
			{ label: "Rejected At", key: "rejected_at" },
		],
		(row) =>
			`<button class='btn btn-primary btn-xs' onclick='showParticipantInfo(${JSON.stringify(
				row
			)})'>View</button>`,
		"Search rejected winners..."
	);
	// Participants
	renderTable(
		tabContentParticipants,
		participants,
		[
			{ label: "Ref. No.", key: "ref_no" },
			{ label: "Name", key: "name" },
			{ label: "Entry At", key: "entry_created_at" },
		],
		(row) =>
			`<button class='btn btn-primary btn-xs' onclick='showParticipantInfo(${JSON.stringify(
				row
			)})'>View</button>`,
		"Search participants..."
	);

	// All entries
	const allEntries = await fetchAllEntries();
	renderTable(
		tabContentAllEntries,
		allEntries,
		[
			{ label: "Ref. No.", key: "ref_no" },
			{ label: "Name", key: "name" },
			{ label: "Entry At", key: "entry_created_at" },
			{ label: "Is Winner", key: "is_winner" },
			{ label: "Winning Date", key: "winning_date" },
		],
		(row) =>
			`<button class='btn btn-primary btn-xs' onclick='showParticipantInfo(${JSON.stringify(
				row
			)})'>View</button>`,
		"Search entries..."
	);
}

// --- Modal Action Handlers ---
window.confirmValidateWinner = function (id, name, email, prize) {
	showModal(
		`Winner <b>${name}</b> is already validated? A confirmation email will be sent to<br><b>${email}</b> to notify for the prize of <b>${prize}</b>.<br>This action cannot be undone.`,
		`<button class='btn btn-primary' onclick='validateWinner("${id}")'>Yes, Validated</button><button class='btn btn-secondary' onclick='closeModal()'>Cancel</button>`
	);
};
window.confirmRejectWinner = function (id, name) {
	showModal(
		`Reject winner <b>${name}</b>? This action cannot be undone.`,
		`<button class='btn btn-danger' onclick='rejectWinner("${id}")'>Yes, Reject</button><button class='btn btn-secondary' onclick='closeModal()'>Cancel</button>`
	);
};
window.confirmBackToNotValidated = function (id, name) {
	showModal(
		`Move <b>${name}</b> back to Not Validated?`,
		`<button class='btn btn-secondary' onclick='backToNotValidated("${id}")'>Yes, Move</button><button class='btn btn-secondary' onclick='closeModal()'>Cancel</button>`
	);
};
window.showParticipantInfo = function (row) {
	let infoHtml = `<div class='mb-2 font-semibold text-lg'>Participant Info</div><div class='space-y-2'>`;
	for (const [k, v] of Object.entries(row)) {
		if (k === "or_photo" && v) {
			infoHtml += `<div style='position:relative;display:inline-block;'>
				<span class='font-semibold capitalize'>OR Photo:</span><br>
				<a href='${v}' target='_blank'>
					<img src='${v}' alt='OR Photo' style='max-width:320px;max-height:426px;border-radius:8px;border:1px solid #e5e7eb;margin-top:4px;box-shadow:0 2px 8px #0001;'>
				</a>
				<button onclick='zoomOrPhoto("${encodeURIComponent(
					v
				)}")' title='Zoom' style='position:absolute;top:8px;right:8px;background:rgba(255,255,255,0.85);border:none;border-radius:50%;padding:6px;cursor:pointer;box-shadow:0 1px 4px #0002;'>
					<svg width='20' height='20' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='11' cy='11' r='7'/><line x1='16' y1='16' x2='21' y2='21'/></svg>
				</button>
			</div>`;
		} else if (k === "entry_created_at" && v) {
			infoHtml += `<div><span class='font-semibold capitalize'>${k.replace(
				/_/g,
				" "
			)}:</span> <span>${v}</span></div>`;
		} else {
			infoHtml += `<div><span class='font-semibold capitalize'>${k.replace(
				/_/g,
				" "
			)}:</span> <span>${v || ""}</span></div>`;
		}
	}
	infoHtml += "</div>";
	showModal(infoHtml);
};

// --- Action Handlers (placeholders) ---
window.validateWinner = async function (id) {
	closeModal();
	showMessage("Validating winner...", "info", 8000);
	try {
		const response = await fetch(`${API_BASE_URL}/validate-winner`, {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ id }),
		});
		const result = await response.json();
		if (!response.ok) {
			throw new Error(result.messages?.error || "Failed to validate winner");
		}
		showMessage(
			result.messages?.success || "Winner validated successfully!",
			"info",
			8000
		);
		// showMessage("Winner validated successfully!", "info");
		await fetchParticipants().then(renderTabs); // Optionally refresh tabs after validation
	} catch (error) {
		showMessage(`Error: ${error.message}`, "error");
	}
};
window.rejectWinner = async function (id) {
	closeModal();
	showMessage("Rejecting winner...", "info", 8000);
	try {
		const response = await fetch(`${API_BASE_URL}/reject-winner`, {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ id }),
		});
		const result = await response.json();
		if (!response.ok) {
			throw new Error(result.messages?.error || "Failed to reject winner");
		}
		showMessage(
			result.messages?.success || "Winner rejected successfully!",
			"info",
			8000
		);
		// showMessage("Winner validated successfully!", "info");
		await fetchParticipants().then(renderTabs); // Optionally refresh tabs after validation
	} catch (error) {
		showMessage(`Error: ${error.message}`, "error");
	}
};
window.backToNotValidated = function (id) {
	closeModal();
	showMessage(`Back to not validated: ${id}`);
};

// --- Tab Logic ---
const tabNotValidated = document.getElementById("tabNotValidated");
const tabValidated = document.getElementById("tabValidated");
const tabRejected = document.getElementById("tabRejected");
const tabParticipants = document.getElementById("tabParticipants");
const tabAllEntries = document.getElementById("tabAllEntries");

const tabContentNotValidated = document.getElementById(
	"tabContentNotValidated"
);
const tabContentValidated = document.getElementById("tabContentValidated");
const tabContentRejected = document.getElementById("tabContentRejected");
const tabContentParticipants = document.getElementById(
	"tabContentParticipants"
);
const tabContentAllEntries = document.getElementById("tabContentAllEntries");

function showTab(tab) {
	tabContentNotValidated.classList.add("hidden");
	tabContentValidated.classList.add("hidden");
	tabContentRejected.classList.add("hidden");
	tabContentParticipants.classList.add("hidden");
	tabContentAllEntries.classList.add("hidden");
	tabNotValidated.classList.remove("border-indigo-500", "text-indigo-700");
	tabValidated.classList.remove("border-indigo-500", "text-indigo-700");
	tabRejected.classList.remove("border-indigo-500", "text-indigo-700");
	tabParticipants.classList.remove("border-indigo-500", "text-indigo-700");
	tabAllEntries.classList.remove("border-indigo-500", "text-indigo-700");
	tabNotValidated.classList.add("border-transparent", "text-gray-600");
	tabValidated.classList.add("border-transparent", "text-gray-600");
	tabRejected.classList.add("border-transparent", "text-gray-600");
	tabParticipants.classList.add("border-transparent", "text-gray-600");
	tabAllEntries.classList.add("border-transparent", "text-gray-600");
	if (tab === "notvalidated") {
		tabContentNotValidated.classList.remove("hidden");
		tabNotValidated.classList.remove("border-transparent", "text-gray-600");
		tabNotValidated.classList.add("border-indigo-500", "text-indigo-700");
	} else if (tab === "validated") {
		tabContentValidated.classList.remove("hidden");
		tabValidated.classList.remove("border-transparent", "text-gray-600");
		tabValidated.classList.add("border-indigo-500", "text-indigo-700");
	} else if (tab === "validated") {
		tabContentValidated.classList.remove("hidden");
		tabValidated.classList.remove("border-transparent", "text-gray-600");
		tabValidated.classList.add("border-indigo-500", "text-indigo-700");
	} else if (tab === "rejected") {
		tabContentRejected.classList.remove("hidden");
		tabRejected.classList.remove("border-transparent", "text-gray-600");
		tabRejected.classList.add("border-indigo-500", "text-indigo-700");
	} else if (tab === "allentries") {
		tabContentAllEntries.classList.remove("hidden");
		tabAllEntries.classList.remove("border-transparent", "text-gray-600");
		tabAllEntries.classList.add("border-indigo-500", "text-indigo-700");
	} else {
		tabContentParticipants.classList.remove("hidden");
		tabParticipants.classList.remove("border-transparent", "text-gray-600");
		tabParticipants.classList.add("border-indigo-500", "text-indigo-700");
	}
}
if (
	tabNotValidated &&
	tabValidated &&
	tabParticipants &&
	tabRejected &&
	tabAllEntries
) {
	tabNotValidated.addEventListener("click", () => showTab("notvalidated"));
	tabValidated.addEventListener("click", () => showTab("validated"));
	tabRejected.addEventListener("click", () => showTab("rejected"));
	tabParticipants.addEventListener("click", () => showTab("participants"));
	tabAllEntries.addEventListener("click", () => showTab("allentries"));
}

// --- Fetch Functions for Winners ---
async function fetchNotValidatedWinners() {
	const res = await fetch(`${API_BASE_URL}/not-validated-winners`);
	return res.ok ? res.json() : [];
}
async function fetchValidatedWinners() {
	const res = await fetch(`${API_BASE_URL}/validated-winners`);
	return res.ok ? res.json() : [];
}
async function fetchRejectedWinners() {
	const res = await fetch(`${API_BASE_URL}/rejected-winners`);
	return res.ok ? res.json() : [];
}
async function fetchAllEntries() {
	const res = await fetch(`${API_BASE_URL}/all-entries`);
	return res.ok ? res.json() : [];
}

// --- Initial Load ---
document.addEventListener("DOMContentLoaded", () => {
	fetchParticipants().then(renderTabs);
	showTab("notvalidated");
});

drawRaffleBtn.addEventListener("click", drawWinner);
