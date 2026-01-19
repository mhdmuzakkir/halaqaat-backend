```javascript
const mongoose = require('mongoose');

const examMarksSchema = new mongoose.Schema({
  examId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Exam',
    required: true
  },
  studentId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Student',
    required: true
  },
  halqaId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Halqa',
    required: true
  },
  shuba: {
    type: String,
    enum: ['Hifz', 'Qaidah', 'Nazira'],
    required: true
  },
  
  // Filled by Ustaaz
  fromPage: Number,
  toPage: Number,
  sulookNumber: Number,
  mumayyizStatus: {
    type: Boolean,
    default: false
  },
  ustaazSubmittedAt: Date,
  
  // Filled by Mumtaheen
  subjectMarks: Number, // Hifz: 60, Qaidah: 70, Nazira: 60
  husnSawt: { type: Number, default: 0 }, // Only Hifz & Nazira
  tajweed: { type: Number, default: 0 },
  izaafaat: { type: Number, default: 0 },
  sulookMarks: { type: Number, default: 0 },
  extraMarks: { type: Number, default: 0 },
  
  totalMarks: Number,
  grade: String, // A+, A, B, C, D, F
  marksEnteredAt: Date,
  enteredBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User'
  },
  
  createdAt: {
    type: Date,
    default: Date.now
  }
});

// Calculate total marks before saving
examMarksSchema.pre('save', function(next) {
  if (this.subjectMarks !== undefined) {
    this.totalMarks = (this.subjectMarks || 0) + 
                      (this.husnSawt || 0) + 
                      (this.tajweed || 0) + 
                      (this.izaafaat || 0) + 
                      (this.sulookMarks || 0) + 
                      (this.extraMarks || 0);
    
    // Calculate grade
    if (this.totalMarks >= 85) this.grade = 'ممتاز';
    else if (this.totalMarks >= 70) this.grade = 'جيد جدًا';
    else if (this.totalMarks >= 55) this.grade = 'جيد';
    else if (this.totalMarks >= 40) this.grade = 'مقبول';
    else this.grade = 'ضعيف';
  }
  next();
});

module.exports = mongoose.model('ExamMarks', examMarksSchema);
```
