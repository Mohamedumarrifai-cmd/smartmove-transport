// Run with mongosh after 01_collections.js and after selecting the SmartMove database.
// Numeric IDs correspond to database/oracle/03_sample_data.sql.
const sampleVehicles = [
	{
		_id: "vehicle-1",
		vehicleId: 1,
		registrationNumber: "SM-2048",
		make: "Toyota",
		model: "Coaster",
		vehicleType: "MINIBUS",
		documents: [
			{ kind: "vehicle_image", path: "assets/images/SM-2048-front.jpg", uploadedAt: ISODate("2026-07-15T09:00:00Z") },
			{ kind: "insurance", path: "uploads/vehicles/SM-2048-insurance.pdf", uploadedAt: ISODate("2026-07-15T09:05:00Z") }
		],
		createdAt: ISODate("2026-07-15T09:00:00Z")
	},
	{
		_id: "vehicle-2",
		vehicleId: 2,
		registrationNumber: "SM-3107",
		make: "Scania",
		model: "Touring",
		vehicleType: "COACH",
		documents: [
			{ kind: "vehicle_image", path: "assets/images/SM-3107-front.jpg", uploadedAt: ISODate("2026-07-18T10:30:00Z") },
			{ kind: "inspection", path: "uploads/vehicles/SM-3107-inspection.pdf", uploadedAt: ISODate("2026-07-18T10:35:00Z") }
		],
		createdAt: ISODate("2026-07-18T10:30:00Z")
	},
	{
		_id: "vehicle-3",
		vehicleId: 3,
		registrationNumber: "SM-1186",
		make: "Isuzu",
		model: "NQR",
		vehicleType: "BUS",
		documents: [
			{ kind: "vehicle_image", path: "assets/images/SM-1186-side.jpg", uploadedAt: ISODate("2026-07-20T08:15:00Z") }
		],
		createdAt: ISODate("2026-07-20T08:15:00Z")
	}
];

sampleVehicles.forEach((document) => {
	db.VehicleImages.replaceOne({ _id: document._id }, document, { upsert: true });
});

const sampleReviews = [
	{
		_id: "review-1",
		passengerId: 1,
		tripId: 1,
		routeId: 1,
		vehicleId: 2,
		rating: 5,
		comments: "Comfortable coach and an on-time arrival.",
		createdAt: ISODate("2026-08-12T12:05:00Z")
	},
	{
		_id: "review-2",
		passengerId: 2,
		tripId: 2,
		routeId: 2,
		vehicleId: 1,
		rating: 4,
		comments: "Friendly driver and a smooth journey.",
		createdAt: ISODate("2026-08-18T12:10:00Z")
	},
	{
		_id: "review-3",
		passengerId: 3,
		tripId: 3,
		routeId: 3,
		vehicleId: 2,
		rating: 2,
		comments: "The air conditioning was broken and the cabin was uncomfortable.",
		createdAt: ISODate("2026-09-21T16:20:00Z")
	},
	{
		_id: "review-4",
		passengerId: 2,
		tripId: 1,
		routeId: 1,
		vehicleId: 2,
		rating: 4,
		comments: "Good service, but the seat was uncomfortable.",
		createdAt: ISODate("2026-08-13T08:45:00Z")
	}
];

sampleReviews.forEach((document) => {
	db.PassengerReviews.replaceOne({ _id: document._id }, document, { upsert: true });
});

const sampleAnnouncements = [
	{
		_id: "announcement-1",
		title: "Accra terminal service update",
		message: "Allow extra time for boarding at the Accra terminal this weekend.",
		routeId: 1,
		publishedAt: ISODate("2026-09-25T08:00:00Z"),
		expiresAt: ISODate("2026-10-02T23:59:59Z"),
		active: true
	},
	{
		_id: "announcement-2",
		title: "Holiday travel schedule",
		message: "Additional departures are scheduled for the October holiday period.",
		publishedAt: ISODate("2026-09-20T09:00:00Z"),
		expiresAt: null,
		active: true
	}
];

sampleAnnouncements.forEach((document) => {
	db.TravelAnnouncements.replaceOne({ _id: document._id }, document, { upsert: true });
});
