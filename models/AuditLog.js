const mongoose = require('mongoose');

const auditLogSchema = new mongoose.Schema({
  userId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  action: {
    type: String,
    required: true
  },
  halqaId: mongoose.Schema.Types.ObjectId,
  studentId: mongoose.Schema.Types.ObjectId,
  examId: mongoose.Schema.Types.ObjectId,
  details: mongoose.Schema.Types.Mixed,
  timestamp: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('AuditLog', auditLogSchema);
