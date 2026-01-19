const mongoose = require('mongoose');

const halqaSchema = new mongoose.Schema({
  name: {
    type: String,
    required: true,
    trim: true
  },
  halqaNumber: {
    type: Number,
    required: true,
    min: 1,
    max: 22,
    unique: true
  },
  gender: {
    type: String,
    enum: ['boys', 'girls'],
    required: true
  },
  ustaazId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  mushrieenId: [{
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User'
  }],
  studentCount: {
    type: Number,
    default: 0
  },
  academicYear: {
    type: String,
    default: '2025-2026'
  },
  createdAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('Halqa', halqaSchema);

