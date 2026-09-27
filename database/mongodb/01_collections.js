// Run with mongosh after selecting the SmartMove database.
// Collection creation is safe to rerun; existing collections are left intact.
const smartMoveCollections = [
	{
		name: "VehicleImages",
		validator: {
			$jsonSchema: {
				bsonType: "object",
				required: ["vehicleId", "registrationNumber", "make", "model", "documents", "createdAt"],
				properties: {
					vehicleId: { bsonType: ["int", "long", "double"], minimum: 1 },
					registrationNumber: { bsonType: "string", minLength: 1 },
					make: { bsonType: "string", minLength: 1 },
					model: { bsonType: "string", minLength: 1 },
					vehicleType: { bsonType: "string" },
					documents: {
						bsonType: "array",
						items: {
							bsonType: "object",
							required: ["kind", "path"],
							properties: {
								kind: { bsonType: "string" },
								path: { bsonType: "string" },
								uploadedAt: { bsonType: "date" }
							}
						}
					},
					createdAt: { bsonType: "date" }
				}
			}
		}
	},
	{
		name: "PassengerReviews",
		validator: {
			$jsonSchema: {
				bsonType: "object",
				required: ["passengerId", "tripId", "routeId", "vehicleId", "rating", "comments", "createdAt"],
				properties: {
					passengerId: { bsonType: ["int", "long", "double"], minimum: 1 },
					tripId: { bsonType: ["int", "long", "double"], minimum: 1 },
					routeId: { bsonType: ["int", "long", "double"], minimum: 1 },
					vehicleId: { bsonType: ["int", "long", "double"], minimum: 1 },
					rating: { bsonType: ["int", "long", "double"], minimum: 1, maximum: 5 },
					comments: { bsonType: "string" },
					createdAt: { bsonType: "date" }
				}
			}
		}
	},
	{
		name: "TravelAnnouncements",
		validator: {
			$jsonSchema: {
				bsonType: "object",
				required: ["title", "message", "publishedAt", "active"],
				properties: {
					title: { bsonType: "string", minLength: 1 },
					message: { bsonType: "string", minLength: 1 },
					routeId: { bsonType: ["int", "long", "double", "null"] },
					publishedAt: { bsonType: "date" },
					expiresAt: { bsonType: ["date", "null"] },
					active: { bsonType: "bool" }
				}
			}
		}
	}
];

const existingSmartMoveCollections = db.getCollectionNames();
smartMoveCollections.forEach(({ name, validator }) => {
	if (!existingSmartMoveCollections.includes(name)) {
		db.createCollection(name, { validator, validationLevel: "strict", validationAction: "error" });
	}
});

db.VehicleImages.createIndex({ vehicleId: 1 }, { unique: true });
db.PassengerReviews.createIndex({ routeId: 1, createdAt: -1 });
db.PassengerReviews.createIndex({ vehicleId: 1, rating: -1 });
db.PassengerReviews.createIndex({ comments: "text" });
db.TravelAnnouncements.createIndex({ active: 1, publishedAt: -1 });
