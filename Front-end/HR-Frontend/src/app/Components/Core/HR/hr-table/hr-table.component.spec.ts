import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrTableComponent } from './hr-table.component';

describe('HrTableComponent', () => {
  let component: HrTableComponent;
  let fixture: ComponentFixture<HrTableComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrTableComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrTableComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
