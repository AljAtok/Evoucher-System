	<script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        

        .inner-container {
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 950px;
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

        #modalOverlay {
			z-index: 9999 !important;
		}
		#modalBox {
			box-shadow: 0 8px 32px rgba(0,0,0,0.25), 0 1.5px 8px rgba(0,0,0,0.10);
		}
    </style>
	<?=$top_nav?>
    <div class="admin-content flex justify-center items-center">
		<div class="inner-container">
			<h1 class="section-title">🎉 Raffle Draw Applicator 🎉</h1>
	
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

			<!-- Tabs for Winners and Participants -->
			<div class="mt-8">
				<div class="flex border-b mb-4">
					<button id="tabNotValidated" class="tab-btn px-4 py-2 font-semibold border-b-2 border-indigo-500 text-indigo-700">Not Validated Winners</button>
					<button id="tabValidated" class="tab-btn px-4 py-2 font-semibold border-b-2 border-transparent text-gray-600">Validated Winners</button>
					<button id="tabRejected" class="tab-btn px-4 py-2 font-semibold border-b-2 border-transparent text-gray-600">Rejected Winners</button>
					<button id="tabParticipants" class="tab-btn px-4 py-2 font-semibold border-b-2 border-transparent text-gray-600">Participants</button>
					<button id="tabAllEntries" class="tab-btn px-4 py-2 font-semibold border-b-2 border-transparent text-gray-600">All Entries</button>
				</div>
				<div id="tabContentNotValidated" class="tab-content"></div>
				<div id="tabContentValidated" class="tab-content hidden"></div>
				<div id="tabContentRejected" class="tab-content hidden"></div>
				<div id="tabContentParticipants" class="tab-content hidden"></div>
				<div id="tabContentAllEntries" class="tab-content hidden"></div>
			</div>

			<!-- <h2 class="section-title text-xl">Current Participants</h2>
			<div id="participantsList" class="participants-grid">
				
				<div class="text-gray-500 text-center col-span-full" id="noParticipantsMessage">No participants yet. Add some!</div>
			</div> -->
		</div>
	</div>
	<!-- Modal for confirmation/details -->
<div id="modalOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
  <div id="modalBox" class="bg-white rounded-lg shadow-lg p-6 min-w-[300px] max-w-[90vw]">
    <div id="modalContent"></div>
    <div class="mt-4 flex justify-end gap-2" id="modalActions"></div>
  </div>
</div>

