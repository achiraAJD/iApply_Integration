USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddApprovalGamesMachines]    Script Date: 1/07/2022 11:54:41 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		David Dennis
-- Create date: 12/09/2017
-- Description:	Add a record into ApprovalGamesMachines
-- =============================================
ALTER PROCEDURE [dbo].[spWebAddApprovalGamesMachines]
	@AGM_APP_ID AS numeric(18,0),
	@AGM_CB_ID_GameApprovalType AS numeric(18,0),
	@AGM_Manufacturer AS varchar(200),
	@AGM_Description AS varchar(200),
	@AGM_ChipsetLabelID AS varchar(200) = NULL,
	@AGM_GameID AS varchar(50),
	@AGM_ShellID AS varchar(50),
	@AGM_CB_ID_InterstateApproval AS numeric(18,0) = NULL,
	@AGM_TestingATF AS varchar(50) = NULL,
	@AGM_IsGMA AS bit = NULL,
	@AGM_IsCAS AS bit = NULL,
	@AGM_ApprovalDate AS datetime = NULL,
	@AGM_OrderNumber AS varchar(50) = NULL,
	@AGM_ExpiryDate AS datetime = NULL,
	@AGM_RenewalDate AS datetime = NULL,
	@AGM_RenewlOrderNumber AS varchar(20) = NULL,
	@AGM_RenewalExpiryDate AS datetime = NULL,
	@AGM_CB_ID_Status AS numeric(18,0),
	@AGM_HasBNA AS bit = NULL,
	@AGM_HasTITO AS bit = NULL
AS
BEGIN

DECLARE @CreationDateTime AS datetime
DECLARE @CreationUser AS varchar(50)
DECLARE @LastUpdateDateTime AS datetime
DECLARE @LastUpdateUser AS varchar(50)

SET @CreationDateTime = GETDATE()
SET @CreationUser = 'SYSTM'
SET @LastUpdateDateTime = GETDATE()
SET @LastUpdateUser = 'SYSTM'

INSERT INTO [dbo].[ApprovalGamesMachines]
           ([AGM_APP_ID]
           ,[AGM_CB_ID_GameApprovalType]
           ,[AGM_Manufacturer]
           ,[AGM_Description]
           ,[AGM_ChipsetLabelID]
           ,[AGM_GameID]
           ,[AGM_ShellID]
           ,[AGM_CB_ID_InterstateApproval]
           ,[AGM_TestingATF]
           ,[AGM_IsGMA]
           ,[AGM_IsCAS]
           ,[AGM_ApprovalDate]
           ,[AGM_OrderNumber]
           ,[AGM_ExpiryDate]
           ,[AGM_RenewalDate]
           ,[AGM_RenewlOrderNumber]
           ,[AGM_RenewalExpiryDate]
           ,[AGM_CB_ID_Status]
           ,[CreationDateTime]
           ,[CreationUser]
           ,[LastUpdateDateTime]
           ,[LastUpdateUser],
		   AGM_HasBNA,
		   AGM_HasTITO)
     VALUES
           (@AGM_APP_ID
           ,@AGM_CB_ID_GameApprovalType
           ,@AGM_Manufacturer
           ,@AGM_Description
           ,@AGM_ChipsetLabelID
           ,@AGM_GameID
           ,@AGM_ShellID
           ,@AGM_CB_ID_InterstateApproval
           ,@AGM_TestingATF
           ,@AGM_IsGMA
           ,@AGM_IsCAS
           ,@AGM_ApprovalDate
           ,@AGM_OrderNumber
           ,@AGM_ExpiryDate
           ,@AGM_RenewalDate
           ,@AGM_RenewlOrderNumber
           ,@AGM_RenewalExpiryDate
           ,@AGM_CB_ID_Status
           ,@CreationDateTime
           ,@CreationUser
           ,@LastUpdateDateTime
           ,@LastUpdateUser,
		   @AGM_HasBNA,
		   @AGM_HasTITO)

		    Select SCOPE_IDENTITY() as AGM_ID
END


