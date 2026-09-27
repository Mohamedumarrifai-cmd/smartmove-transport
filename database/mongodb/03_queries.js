// Run each section with mongosh after selecting the SmartMove database.

// 1. Passenger reviews for a specific route.
const routeId = 1;
db.PassengerReviews.find({ routeId: routeId })
	.sort({ createdAt: -1 })
	.pretty();

// 2. Highest-rated vehicles, ranked by average rating across submitted reviews.
db.PassengerReviews.aggregate([
	{
		$group: {
			_id: "$vehicleId",
			averageRating: { $avg: "$rating" },
			reviewCount: { $sum: 1 }
		}
	},
	{ $sort: { averageRating: -1, reviewCount: -1, _id: 1 } },
	{
		$lookup: {
			from: "VehicleImages",
			localField: "_id",
			foreignField: "vehicleId",
			as: "vehicle"
		}
	},
	{ $unwind: { path: "$vehicle", preserveNullAndEmptyArrays: true } },
	{
		$project: {
			_id: 0,
			vehicleId: "$_id",
			registrationNumber: "$vehicle.registrationNumber",
			make: "$vehicle.make",
			model: "$vehicle.model",
			averageRating: { $round: ["$averageRating", 2] },
			reviewCount: 1
		}
	}
]);

// 3. Search review complaints by one or more case-insensitive keywords.
const complaintKeywords = ["broken", "uncomfortable", "delay"];
const complaintPattern = complaintKeywords.join("|");
db.PassengerReviews.find({
	comments: { $regex: complaintPattern, $options: "i" }
}).sort({ createdAt: -1 }).pretty();

// 4. Retrieve vehicle records and their image/document metadata.
db.VehicleImages.find({}, {
	_id: 0,
	vehicleId: 1,
	registrationNumber: 1,
	make: 1,
	model: 1,
	vehicleType: 1,
	documents: 1,
	createdAt: 1
}).sort({ vehicleId: 1 }).pretty();
