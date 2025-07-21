import type { FunctionTemplate } from './index.js';

export const EDUCATION_TEMPLATE: FunctionTemplate = {
  functions: {
    // ================================
    // GPA & GRADING SYSTEMS
    // ================================
    
    'calculate_gpa': (grades: Array<{ grade: string, credits: number }>): { gpa: number, totalCredits: number, qualityPoints: number } => {
      if (!Array.isArray(grades) || grades.length === 0) {
        throw new Error('Grades array must be non-empty');
      }
      
      // Grade point mapping (4.0 scale)
      const gradePoints: Record<string, number> = {
        'A': 4.0, 'A-': 3.7,
        'B+': 3.3, 'B': 3.0, 'B-': 2.7,
        'C+': 2.3, 'C': 2.0, 'C-': 1.7,
        'D+': 1.3, 'D': 1.0, 'D-': 0.7,
        'F': 0.0
      };
      
      let totalQualityPoints = 0;
      let totalCredits = 0;
      
      for (const { grade, credits } of grades) {
        if (credits <= 0) {
          throw new Error('Credits must be positive');
        }
        
        const points = gradePoints[grade.toUpperCase()];
        if (points === undefined) {
          throw new Error(`Invalid grade: ${grade}`);
        }
        
        totalQualityPoints += points * credits;
        totalCredits += credits;
      }
      
      const gpa = totalCredits > 0 ? totalQualityPoints / totalCredits : 0;
      
      return {
        gpa: Math.round(gpa * 100) / 100,
        totalCredits,
        qualityPoints: Math.round(totalQualityPoints * 100) / 100
      };
    },

    'grade_to_point': (grade: string, scale: string = '4.0'): number => {
      const grade4Scale: Record<string, number> = {
        'A': 4.0, 'A-': 3.7,
        'B+': 3.3, 'B': 3.0, 'B-': 2.7,
        'C+': 2.3, 'C': 2.0, 'C-': 1.7,
        'D+': 1.3, 'D': 1.0, 'D-': 0.7,
        'F': 0.0
      };
      
      const gradeThaiScale: Record<string, number> = {
        'A': 4.0, 'B+': 3.5, 'B': 3.0, 'C+': 2.5,
        'C': 2.0, 'D+': 1.5, 'D': 1.0, 'F': 0.0
      };
      
      const gradeMapping = scale === 'thai' ? gradeThaiScale : grade4Scale;
      const points = gradeMapping[grade.toUpperCase()];
      
      if (points === undefined) {
        throw new Error(`Invalid grade: ${grade} for scale: ${scale}`);
      }
      
      return points;
    },

    'academic_standing': (gpa: number, totalCredits: number = 0): string => {
      if (gpa < 0 || gpa > 4.0) {
        throw new Error('GPA must be between 0 and 4.0');
      }
      
      // Academic standing based on GPA
      if (gpa >= 3.8) return 'Summa Cum Laude';
      if (gpa >= 3.6) return 'Magna Cum Laude';
      if (gpa >= 3.4) return 'Cum Laude';
      if (gpa >= 3.0) return 'Good Standing';
      if (gpa >= 2.5) return 'Satisfactory';
      if (gpa >= 2.0) return 'Probation Warning';
      return 'Academic Probation';
    },

    'credits_to_graduate': (currentCredits: number, programRequirement: number = 120): { remaining: number, progress: number, canGraduate: boolean } => {
      if (currentCredits < 0 || programRequirement <= 0) {
        throw new Error('Credits must be non-negative and program requirement must be positive');
      }
      
      const remaining = Math.max(0, programRequirement - currentCredits);
      const progress = Math.min(100, (currentCredits / programRequirement) * 100);
      const canGraduate = currentCredits >= programRequirement;
      
      return {
        remaining,
        progress: Math.round(progress * 100) / 100,
        canGraduate
      };
    },

    'semester_gpa': (semesterGrades: Array<{ score: number, credits: number }>): number => {
      if (!Array.isArray(semesterGrades) || semesterGrades.length === 0) {
        throw new Error('Semester grades array must be non-empty');
      }
      
      let totalWeightedScore = 0;
      let totalCredits = 0;
      
      for (const { score, credits } of semesterGrades) {
        if (score < 0 || score > 100) {
          throw new Error('Score must be between 0 and 100');
        }
        if (credits <= 0) {
          throw new Error('Credits must be positive');
        }
        
        // Convert score to 4.0 scale
        let gradePoint = 0;
        if (score >= 95) gradePoint = 4.0;
        else if (score >= 90) gradePoint = 3.7;
        else if (score >= 87) gradePoint = 3.3;
        else if (score >= 83) gradePoint = 3.0;
        else if (score >= 80) gradePoint = 2.7;
        else if (score >= 77) gradePoint = 2.3;
        else if (score >= 73) gradePoint = 2.0;
        else if (score >= 70) gradePoint = 1.7;
        else if (score >= 67) gradePoint = 1.3;
        else if (score >= 65) gradePoint = 1.0;
        else if (score >= 60) gradePoint = 0.7;
        else gradePoint = 0.0;
        
        totalWeightedScore += gradePoint * credits;
        totalCredits += credits;
      }
      
      return totalCredits > 0 ? Math.round((totalWeightedScore / totalCredits) * 100) / 100 : 0;
    },

    // ================================
    // STUDENT PERFORMANCE ANALYSIS
    // ================================
    
    'grade_distribution': (scores: number[]): { A: number, B: number, C: number, D: number, F: number, average: number } => {
      if (!Array.isArray(scores) || scores.length === 0) {
        throw new Error('Scores array must be non-empty');
      }
      
      let gradeCount = { A: 0, B: 0, C: 0, D: 0, F: 0 };
      let total = 0;
      
      for (const score of scores) {
        if (score < 0 || score > 100) {
          throw new Error('Score must be between 0 and 100');
        }
        
        total += score;
        
        if (score >= 90) gradeCount.A++;
        else if (score >= 80) gradeCount.B++;
        else if (score >= 70) gradeCount.C++;
        else if (score >= 60) gradeCount.D++;
        else gradeCount.F++;
      }
      
      const average = total / scores.length;
      
      return {
        ...gradeCount,
        average: Math.round(average * 100) / 100
      };
    },

    'class_rank': (studentScore: number, allScores: number[]): { rank: number, total: number, percentile: number } => {
      if (!Array.isArray(allScores) || allScores.length === 0) {
        throw new Error('All scores array must be non-empty');
      }
      
      // Count students with higher scores
      const higherScores = allScores.filter(score => score > studentScore).length;
      const rank = higherScores + 1;
      const total = allScores.length;
      
      // Calculate percentile (percentage of students scored lower)
      const lowerScores = allScores.filter(score => score < studentScore).length;
      const percentile = total > 1 ? (lowerScores / (total - 1)) * 100 : 100;
      
      return {
        rank,
        total,
        percentile: Math.round(percentile * 100) / 100
      };
    },

    'attendance_rate': (attendedClasses: number, totalClasses: number): { rate: number, status: string, allowedAbsences: number } => {
      if (attendedClasses < 0 || totalClasses <= 0 || attendedClasses > totalClasses) {
        throw new Error('Invalid attendance data');
      }
      
      const rate = (attendedClasses / totalClasses) * 100;
      const absences = totalClasses - attendedClasses;
      const allowedAbsences = Math.floor(totalClasses * 0.2); // 20% absence limit
      
      let status: string;
      if (rate >= 95) status = 'Excellent';
      else if (rate >= 90) status = 'Good';
      else if (rate >= 80) status = 'Satisfactory';
      else if (rate >= 70) status = 'Warning';
      else status = 'Critical';
      
      return {
        rate: Math.round(rate * 100) / 100,
        status,
        allowedAbsences: Math.max(0, allowedAbsences - absences)
      };
    },

    // ================================
    // ACADEMIC PLANNING
    // ================================
    
    'course_difficulty': (averageGpa: number, passRate: number, dropRate: number): { difficulty: string, score: number, recommendation: string } => {
      if (averageGpa < 0 || averageGpa > 4.0 || passRate < 0 || passRate > 100 || dropRate < 0 || dropRate > 100) {
        throw new Error('Invalid input values');
      }
      
      // Calculate difficulty score (0-100, higher = more difficult)
      const gpaFactor = (4.0 - averageGpa) / 4.0 * 40; // 0-40 points
      const passRateFactor = (100 - passRate) / 100 * 35; // 0-35 points
      const dropRateFactor = dropRate / 100 * 25; // 0-25 points
      
      const score = gpaFactor + passRateFactor + dropRateFactor;
      
      let difficulty: string;
      let recommendation: string;
      
      if (score >= 80) {
        difficulty = 'Very Hard';
        recommendation = 'Consider prerequisites and time management. Study group recommended.';
      } else if (score >= 60) {
        difficulty = 'Hard';
        recommendation = 'Plan extra study time. Consider office hours.';
      } else if (score >= 40) {
        difficulty = 'Moderate';
        recommendation = 'Standard preparation should be sufficient.';
      } else if (score >= 20) {
        difficulty = 'Easy';
        recommendation = 'Good choice for heavy semester load.';
      } else {
        difficulty = 'Very Easy';
        recommendation = 'Minimal effort required.';
      }
      
      return {
        difficulty,
        score: Math.round(score * 100) / 100,
        recommendation
      };
    },

    'graduation_timeline': (currentCredits: number, creditsPerSemester: number, totalRequired: number = 120): { semesters: number, years: number, graduationDate: string } => {
      if (currentCredits < 0 || creditsPerSemester <= 0 || totalRequired <= 0) {
        throw new Error('Invalid input values');
      }
      
      const remainingCredits = Math.max(0, totalRequired - currentCredits);
      const semesters = Math.ceil(remainingCredits / creditsPerSemester);
      const years = Math.ceil(semesters / 2); // 2 semesters per year
      
      // Calculate approximate graduation date (assuming fall/spring semesters)
      const now = new Date();
      const currentMonth = now.getMonth();
      
      // Determine next semester start
      let nextSemesterStart: Date;
      if (currentMonth >= 8) { // September or later - next semester is spring
        nextSemesterStart = new Date(now.getFullYear() + 1, 0, 15); // January 15
      } else if (currentMonth >= 0) { // January to August - next semester is fall
        nextSemesterStart = new Date(now.getFullYear(), 7, 15); // August 15
      } else {
        nextSemesterStart = new Date(now.getFullYear(), 0, 15);
      }
      
      // Add semesters to get graduation date
      const graduationDate = new Date(nextSemesterStart);
      graduationDate.setMonth(graduationDate.getMonth() + (semesters * 6)); // 6 months per semester
      
      return {
        semesters,
        years,
        graduationDate: graduationDate.toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        })
      };
    },

    'scholarship_eligibility': (gpa: number, credits: number, financialNeed: number, extracurriculars: number): { eligible: boolean, score: number, recommendations: string[] } => {
      if (gpa < 0 || gpa > 4.0 || credits < 0 || financialNeed < 0 || financialNeed > 100 || extracurriculars < 0) {
        throw new Error('Invalid input values');
      }
      
      // Scoring system (0-100)
      const gpaScore = (gpa / 4.0) * 40; // 40% weight
      const creditScore = Math.min(credits / 30, 1) * 25; // 25% weight (30+ credits = full score)
      const needScore = financialNeed * 0.20; // 20% weight
      const activityScore = Math.min(extracurriculars / 5, 1) * 15; // 15% weight (5+ activities = full score)
      
      const totalScore = gpaScore + creditScore + needScore + activityScore;
      const eligible = totalScore >= 70; // 70% threshold
      
      const recommendations: string[] = [];
      
      if (gpa < 3.0) recommendations.push('Improve GPA to at least 3.0');
      if (credits < 12) recommendations.push('Complete at least 12 credit hours');
      if (extracurriculars < 2) recommendations.push('Participate in extracurricular activities');
      if (totalScore < 70) recommendations.push('Overall score needs improvement');
      
      if (recommendations.length === 0) {
        recommendations.push('Excellent candidate for scholarship');
      }
      
      return {
        eligible,
        score: Math.round(totalScore * 100) / 100,
        recommendations
      };
    }
  },

  info: {
    name: 'Education Functions',
    category: 'Education',
    version: '1.0.0',
    description: 'Academic and educational calculation functions for GPA, grading, and student performance analysis',
    functions: {
      'calculate_gpa': {
        description: 'Calculate GPA from grades and credits',
        parameters: ['grades'],
        returnType: 'object',
        examples: [
          {
            code: "calculate_gpa([{grade: 'A', credits: 3}, {grade: 'B+', credits: 4}])",
            description: 'Calculate GPA from course grades',
            result: { gpa: 3.61, totalCredits: 7, qualityPoints: 25.2 }
          }
        ]
      },

      'grade_to_point': {
        description: 'Convert letter grade to grade points',
        parameters: ['grade', 'scale?'],
        returnType: 'number',
        examples: [
          {
            code: "grade_to_point('B+', '4.0')",
            description: 'Convert B+ to 4.0 scale',
            result: 3.3
          }
        ]
      },

      'academic_standing': {
        description: 'Determine academic standing based on GPA',
        parameters: ['gpa', 'totalCredits?'],
        returnType: 'string',
        examples: [
          {
            code: "academic_standing(3.8)",
            description: 'High GPA academic standing',
            result: 'Summa Cum Laude'
          }
        ]
      },

      'credits_to_graduate': {
        description: 'Calculate remaining credits and progress to graduation',
        parameters: ['currentCredits', 'programRequirement?'],
        returnType: 'object',
        examples: [
          {
            code: "credits_to_graduate(90, 120)",
            description: 'Progress toward graduation',
            result: { remaining: 30, progress: 75, canGraduate: false }
          }
        ]
      },

      'semester_gpa': {
        description: 'Calculate GPA from numerical scores and credits',
        parameters: ['semesterGrades'],
        returnType: 'number',
        examples: [
          {
            code: "semester_gpa([{score: 95, credits: 3}, {score: 87, credits: 4}])",
            description: 'Semester GPA calculation',
            result: 3.61
          }
        ]
      },

      'grade_distribution': {
        description: 'Analyze grade distribution from scores',
        parameters: ['scores'],
        returnType: 'object',
        examples: [
          {
            code: "grade_distribution([95, 87, 76, 82, 91])",
            description: 'Class grade distribution',
            result: { A: 2, B: 2, C: 1, D: 0, F: 0, average: 86.2 }
          }
        ]
      },

      'class_rank': {
        description: 'Calculate student rank and percentile',
        parameters: ['studentScore', 'allScores'],
        returnType: 'object',
        examples: [
          {
            code: "class_rank(85, [78, 82, 85, 90, 95])",
            description: 'Student ranking in class',
            result: { rank: 3, total: 5, percentile: 50 }
          }
        ]
      },

      'attendance_rate': {
        description: 'Calculate attendance rate and status',
        parameters: ['attendedClasses', 'totalClasses'],
        returnType: 'object',
        examples: [
          {
            code: "attendance_rate(28, 30)",
            description: 'Attendance analysis',
            result: { rate: 93.33, status: 'Good', allowedAbsences: 4 }
          }
        ]
      },

      'course_difficulty': {
        description: 'Assess course difficulty and provide recommendations',
        parameters: ['averageGpa', 'passRate', 'dropRate'],
        returnType: 'object',
        examples: [
          {
            code: "course_difficulty(2.1, 65, 25)",
            description: 'Course difficulty assessment',
            result: { difficulty: 'Hard', score: 75.5, recommendation: 'Plan extra study time...' }
          }
        ]
      },

      'graduation_timeline': {
        description: 'Calculate graduation timeline and date',
        parameters: ['currentCredits', 'creditsPerSemester', 'totalRequired?'],
        returnType: 'object',
        examples: [
          {
            code: "graduation_timeline(90, 15, 120)",
            description: 'Graduation planning',
            result: { semesters: 2, years: 1, graduationDate: 'May 15, 2026' }
          }
        ]
      },

      'scholarship_eligibility': {
        description: 'Evaluate scholarship eligibility and provide recommendations',
        parameters: ['gpa', 'credits', 'financialNeed', 'extracurriculars'],
        returnType: 'object',
        examples: [
          {
            code: "scholarship_eligibility(3.5, 30, 80, 3)",
            description: 'Scholarship evaluation',
            result: { eligible: true, score: 85, recommendations: ['Excellent candidate...'] }
          }
        ]
      }
    }
  }
};